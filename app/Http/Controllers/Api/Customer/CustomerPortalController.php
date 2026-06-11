<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\LoyaltyTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\Table;
use App\Services\Inventory\ProductStockService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerPortalController extends Controller
{
    public function home(Request $request): JsonResponse
    {
        /** @var \App\Models\Customer $customer */
        $customer = $request->user();

        $recentOrders = Order::query()
            ->with(['branch.restaurant', 'items.product'])
            ->where('customer_id', $customer->id)
            ->latest('order_date')
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (Order $order) => $this->transformOrder($order));

        $restaurants = Restaurant::query()
            ->withCount('branches')
            ->with(['branches' => fn ($query) => $query
                ->select('id', 'restaurant_id', 'name', 'location')
                ->orderBy('name')
                ->limit(3)])
            ->orderBy('name')
            ->limit(8)
            ->get()
            ->map(fn (Restaurant $restaurant) => $this->transformRestaurant($restaurant));

        $loyalty = LoyaltyTransaction::query()
            ->where('customer_id', $customer->id)
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (LoyaltyTransaction $transaction) => $this->transformLoyalty($transaction));

        return response()->json([
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'loyalty_points' => $customer->loyalty_points,
            ],
            'restaurants' => $restaurants,
            'recent_orders' => $recentOrders,
            'loyalty_preview' => $loyalty,
        ]);
    }

    public function restaurants(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('search'));
        $perPage = max(6, min((int) $request->input('per_page', 12), 30));
        $kind = trim((string) $request->input('kind'));

        $restaurants = Restaurant::query()
            ->withCount('branches')
            ->with(['branches' => fn ($query) => $query
                ->select('id', 'restaurant_id', 'name', 'location')
                ->orderBy('name')])
            ->when(in_array($kind, ['restaurant', 'cafe'], true), fn ($query) => $query->where('kind', $kind))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhereHas('branches', function ($branchQuery) use ($search) {
                            $branchQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('location', 'like', "%{$search}%");
                        });
                    });
            })
            ->orderBy('name')
            ->paginate($perPage);

        return $this->paginatedResponse(
            $restaurants,
            fn (Restaurant $restaurant) => $this->transformRestaurant($restaurant)
        );
    }

    public function restaurant(Request $request, Restaurant $restaurant): JsonResponse
    {
        $search = trim((string) $request->input('search'));
        $perPage = max(6, min((int) $request->input('per_page', 12), 30));
        $page = max(1, (int) $request->input('page', 1));
        $branchId = $request->integer('branch_id') ?: null;

        $restaurant->loadCount('branches')
            ->load(['branches' => fn ($query) => $query
                ->select('id', 'restaurant_id', 'name', 'location')
                ->orderBy('name')]);

        if ($branchId !== null) {
            abort_unless($restaurant->branches->contains('id', $branchId), 404);
        }

        $products = Product::query()
            ->with([
                'branch:id,restaurant_id,name,location',
                'category:id,name',
            ])
            ->whereHas('branch', fn ($query) => $query->where('restaurant_id', $restaurant->id))
            ->where('is_available', true)
            ->when($branchId !== null, fn ($query) => $query->where('branch_id', $branchId))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhereHas('branch', function ($branchQuery) use ($search) {
                            $branchQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('location', 'like', "%{$search}%");
                        })
                        ->orWhereHas('category', fn ($categoryQuery) => $categoryQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderBy('name')
            ->orderBy('id');

        if ($branchId !== null) {
            $paginatedProducts = $products->paginate($perPage);

            return response()->json([
                'restaurant' => $this->transformRestaurant($restaurant),
                'data' => collect($paginatedProducts->items())
                    ->map(fn (Product $product) => $this->transformRestaurantProduct($product))
                    ->values(),
                'meta' => [
                    'current_page' => $paginatedProducts->currentPage(),
                    'last_page' => $paginatedProducts->lastPage(),
                    'per_page' => $paginatedProducts->perPage(),
                    'total' => $paginatedProducts->total(),
                ],
            ]);
        }

        $productGroups = $products
            ->get()
            ->groupBy(fn (Product $product) => $this->productDedupeKey($product))
            ->map(fn (Collection $group) => $group->sortBy('id')->values())
            ->sortBy(fn (Collection $group) => Str::lower($group->first()->name))
            ->values();

        $pageGroups = $productGroups->forPage($page, $perPage)->values();

        return response()->json([
            'restaurant' => $this->transformRestaurant($restaurant),
            'data' => $pageGroups
                ->map(fn (Collection $group) => $this->transformRestaurantProduct($group->first(), $group))
                ->values(),
            'meta' => [
                'current_page' => $page,
                'last_page' => (int) max(1, ceil($productGroups->count() / $perPage)),
                'per_page' => $perPage,
                'total' => $productGroups->count(),
            ],
        ]);
    }

    public function orders(Request $request): JsonResponse
    {
        /** @var \App\Models\Customer $customer */
        $customer = $request->user();
        $perPage = max(5, min((int) $request->input('per_page', 10), 30));

        $orders = Order::query()
            ->with(['branch.restaurant', 'items.product', 'payments'])
            ->where('customer_id', $customer->id)
            ->latest('order_date')
            ->latest('id')
            ->paginate($perPage);

        return $this->paginatedResponse(
            $orders,
            fn (Order $order) => $this->transformOrder($order)
        );
    }

    public function order(Request $request, Order $order): JsonResponse
    {
        /** @var \App\Models\Customer $customer */
        $customer = $request->user();

        abort_unless((int) $order->customer_id === (int) $customer->id, 404);

        $order->load(['branch.restaurant', 'items.product', 'payments']);

        return response()->json([
            'data' => $this->transformOrder($order),
        ]);
    }

    public function checkout(Request $request, ProductStockService $stockService): JsonResponse
    {
        /** @var \App\Models\Customer $customer */
        $customer = $request->user();

        $data = $request->validate([
            'branch_id' => 'required_without:table_id|nullable|integer|exists:branches,id',
            'table_id' => 'nullable|integer|exists:tables,id',
            'order_type' => 'nullable|in:dine-in,takeaway,delivery',
            'payment_method' => 'nullable|in:cash_on_pickup,pay_at_counter,online_pending',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.note' => 'nullable|string|max:255',
        ]);

        $table = !empty($data['table_id'])
            ? Table::query()->with('branch.restaurant')->findOrFail($data['table_id'])
            : null;
        $branch = $table?->branch ?: Branch::query()->with('restaurant')->findOrFail($data['branch_id']);

        if ($table && !empty($data['branch_id']) && (int) $data['branch_id'] !== (int) $table->branch_id) {
            throw ValidationException::withMessages([
                'branch_id' => 'Branch must match the selected table.',
            ]);
        }

        $order = DB::transaction(function () use ($data, $customer, $branch, $table, $stockService): Order {
            $order = Order::query()->create([
                'branch_id' => $branch->id,
                'table_id' => $table?->id,
                'customer_id' => $customer->id,
                'order_type' => $data['order_type'] ?? ($table ? 'dine-in' : 'takeaway'),
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'payment_method' => $data['payment_method'] ?? 'pay_at_counter',
                'subtotal' => 0,
                'tax' => 0,
                'discount' => 0,
                'total' => 0,
                'order_date' => now(),
            ]);

            foreach ($data['items'] as $line) {
                $requestedProduct = Product::query()
                    ->with('category:id,name')
                    ->findOrFail($line['product_id']);
                $requestedCategoryName = $requestedProduct->category?->name;

                $product = Product::query()
                    ->with('category:id,name')
                    ->where('branch_id', $branch->id)
                    ->where('is_available', true)
                    ->where(function ($query) use ($requestedProduct, $requestedCategoryName) {
                        $query->whereKey($requestedProduct->id)
                            ->orWhere(function ($matchQuery) use ($requestedProduct, $requestedCategoryName) {
                                $matchQuery->where('name', $requestedProduct->name)
                                    ->where(function ($categoryQuery) use ($requestedProduct, $requestedCategoryName) {
                                        if (blank($requestedCategoryName)) {
                                            $categoryQuery->whereNull('category_id');

                                            return;
                                        }

                                        $categoryQuery->where('category_id', $requestedProduct->category_id)
                                            ->orWhereHas('category', fn ($branchCategoryQuery) => $branchCategoryQuery
                                                ->where('name', $requestedCategoryName));
                                    });
                            });
                    })
                    ->first();

                if (!$product) {
                    throw ValidationException::withMessages([
                        'items' => ['Selected product is not available for this branch.'],
                    ]);
                }

                $quantity = (int) $line['quantity'];
                $stockService->consume($product, (int) $branch->id, $quantity);

                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price' => (float) $product->price,
                    'total' => round((float) $product->price * $quantity, 2),
                    'status' => 'pending',
                    'kds_status' => 'pending',
                    'item_note' => $line['note'] ?? ($data['notes'] ?? null),
                    'change_note' => $line['note'] ?? null,
                ]);
            }

            if ($table) {
                $table->update(['status' => \App\Enums\TableStatus::OCCUPIED]);
            }

            return \App\Services\Orders\RecalculateOrder::run($order);
        });

        $order->load(['branch.restaurant', 'table', 'items.product', 'payments']);

        return response()->json([
            'data' => $this->transformOrder($order),
            'message' => 'Order placed for branch confirmation.',
        ], 201);
    }

    public function loyalty(Request $request): JsonResponse
    {
        /** @var \App\Models\Customer $customer */
        $customer = $request->user();
        $perPage = max(5, min((int) $request->input('per_page', 10), 30));

        $transactions = LoyaltyTransaction::query()
            ->with(['order.branch.restaurant'])
            ->where('customer_id', $customer->id)
            ->latest('id')
            ->paginate($perPage);

        return $this->paginatedResponse(
            $transactions,
            fn (LoyaltyTransaction $transaction) => $this->transformLoyalty($transaction)
        );
    }

    private function paginatedResponse(LengthAwarePaginator $paginator, callable $transform): JsonResponse
    {
        return response()->json([
            'data' => collect($paginator->items())->map($transform)->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    private function transformRestaurant(Restaurant $restaurant): array
    {
        $featuredItems = Product::query()
            ->with('category:id,name')
            ->whereHas('branch', fn ($query) => $query->where('restaurant_id', $restaurant->id))
            ->where('is_available', true)
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'image', 'branch_id', 'category_id'])
            ->unique(fn (Product $product) => $this->productDedupeKey($product))
            ->take(3)
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'price' => (float) $product->price,
                'image' => $product->image,
            ])
            ->values();

        return [
            'id' => $restaurant->id,
            'name' => $restaurant->name,
            'kind' => $restaurant->kind,
            'branch_count' => $restaurant->branches_count ?? $restaurant->branches->count(),
            'cover_image' => $featuredItems->first()['image'] ?? null,
            'featured_items' => $featuredItems,
            'branches' => $restaurant->branches->map(fn ($branch) => [
                'id' => $branch->id,
                'name' => $branch->name,
                'location' => $branch->location,
            ])->values(),
        ];
    }

    private function transformOrder(Order $order): array
    {
        return [
            'id' => $order->id,
            'restaurant_id' => $order->branch?->restaurant?->id,
            'branch_id' => $order->branch?->id,
            'table_id' => $order->table?->id,
            'table_name' => $order->table?->name,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'payment_method' => $order->payment_method,
            'order_type' => $order->order_type,
            'total' => (float) $order->total,
            'subtotal' => (float) $order->subtotal,
            'created_at' => optional($order->order_date ?? $order->created_at)->toDateTimeString(),
            'restaurant_name' => optional($order->branch?->restaurant)->name,
            'branch_name' => optional($order->branch)->name,
            'branch_location' => optional($order->branch)->location,
            'items' => $order->items->map(fn ($item) => [
                'id' => $item->id,
                'name' => optional($item->product)->name,
                'image' => optional($item->product)->image,
                'quantity' => $item->quantity,
                'total' => (float) $item->total,
            ])->values(),
            'payments' => $order->payments->map(fn ($payment) => [
                'id' => $payment->id,
                'method' => $payment->method,
                'amount' => (float) $payment->amount,
            ])->values(),
        ];
    }

    private function transformLoyalty(LoyaltyTransaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'type' => $transaction->type,
            'points' => $transaction->points,
            'created_at' => optional($transaction->created_at)->toDateTimeString(),
            'order_id' => $transaction->order_id,
            'restaurant_id' => $transaction->order?->branch?->restaurant?->id,
            'branch_id' => $transaction->order?->branch?->id,
            'restaurant_name' => optional($transaction->order?->branch?->restaurant)->name,
            'branch_name' => optional($transaction->order?->branch)->name,
        ];
    }

    private function transformRestaurantProduct(Product $product, ?Collection $availableProducts = null): array
    {
        $availableBranches = ($availableProducts ?? collect([$product]))
            ->map(fn (Product $branchProduct) => $branchProduct->branch)
            ->filter()
            ->unique('id')
            ->map(fn ($branch) => [
                'id' => $branch->id,
                'name' => $branch->name,
                'location' => $branch->location,
            ])
            ->values();

        return [
            'id' => $product->id,
            'name' => $product->name,
            'price' => (float) $product->price,
            'image' => $product->image,
            'branch_id' => $product->branch?->id,
            'branch_name' => $product->branch?->name,
            'branch_location' => $product->branch?->location,
            'category_name' => $product->category?->name,
            'branch_count' => $availableBranches->count(),
            'branches' => $availableBranches,
        ];
    }

    private function productDedupeKey(Product $product): string
    {
        return Str::lower(trim($product->name)).'|'.Str::lower(trim((string) $product->category?->name));
    }
}
