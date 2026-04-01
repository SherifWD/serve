<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyTransaction;
use App\Models\Order;
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

        $restaurants = Restaurant::query()
            ->withCount('branches')
            ->with(['branches' => fn ($query) => $query
                ->select('id', 'restaurant_id', 'name', 'location')
                ->orderBy('name')])
            ->when($search !== '', function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhereHas('branches', function ($branchQuery) use ($search) {
                        $branchQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('location', 'like', "%{$search}%");
                    });
            })
            ->orderBy('name')
            ->paginate($perPage);

        return $this->paginatedResponse(
            $restaurants,
            fn (Restaurant $restaurant) => $this->transformRestaurant($restaurant)
        );
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
        return [
            'id' => $restaurant->id,
            'name' => $restaurant->name,
            'branch_count' => $restaurant->branches_count ?? $restaurant->branches->count(),
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
            'restaurant_name' => optional($transaction->order?->branch?->restaurant)->name,
            'branch_name' => optional($transaction->order?->branch)->name,
        ];
    }
}
