<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyTransaction;
use App\Models\Order;
use App\Models\Product;
use App\Models\Restaurant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        $restaurant->loadCount('branches')
            ->load(['branches' => fn ($query) => $query
                ->select('id', 'restaurant_id', 'name', 'location')
                ->orderBy('name')]);

        $products = Product::query()
            ->with([
                'branch:id,restaurant_id,name,location',
                'category:id,name',
            ])
            ->whereHas('branch', fn ($query) => $query->where('restaurant_id', $restaurant->id))
            ->where('is_available', true)
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
            ->paginate($perPage);

        return response()->json([
            'restaurant' => $this->transformRestaurant($restaurant),
            'data' => collect($products->items())
                ->map(fn (Product $product) => $this->transformRestaurantProduct($product))
                ->values(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
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
            ->whereHas('branch', fn ($query) => $query->where('restaurant_id', $restaurant->id))
            ->where('is_available', true)
            ->orderBy('name')
            ->limit(3)
            ->get(['id', 'name', 'price', 'image', 'branch_id'])
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

    private function transformRestaurantProduct(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'price' => (float) $product->price,
            'image' => $product->image,
            'branch_id' => $product->branch?->id,
            'branch_name' => $product->branch?->name,
            'branch_location' => $product->branch?->location,
            'category_name' => $product->category?->name,
        ];
    }
}
