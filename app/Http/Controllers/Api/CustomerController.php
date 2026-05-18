<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $query = Customer::query()
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            });

        if ($this->shouldConstrainCustomersByOrders($request, $filters)) {
            $query->whereHas('orders', fn (Builder $ordersQuery) => $this->applyOrderFilters($request, $ordersQuery, $filters));
        }

        $query
            ->with([
                'orders' => fn ($ordersQuery) => $this->applyOrderFilters($request, $ordersQuery, $filters)
                    ->with(['branch.restaurant:id,name,kind,logo_url', 'table:id,name', 'payments'])
                    ->latest('order_date')
                    ->latest('id'),
            ])
            ->withCount(['orders as purchases_count' => fn (Builder $ordersQuery) => $this->applyOrderFilters($request, $ordersQuery, $filters)])
            ->withSum(['orders as total_spent' => fn (Builder $ordersQuery) => $this->applyOrderFilters($request, $ordersQuery, $filters)], 'total')
            ->withAvg(['orders as average_bill' => fn (Builder $ordersQuery) => $this->applyOrderFilters($request, $ordersQuery, $filters)], 'total')
            ->withMax(['orders as highest_bill' => fn (Builder $ordersQuery) => $this->applyOrderFilters($request, $ordersQuery, $filters)], 'total')
            ->withMax(['orders as last_visit_at' => fn (Builder $ordersQuery) => $this->applyOrderFilters($request, $ordersQuery, $filters)], 'order_date')
            ->orderByDesc('last_visit_at')
            ->orderBy('name');

        $perPage = (int) ($filters['per_page'] ?? 20);
        $paginator = $query->paginate($perPage);

        $orderAggregateQuery = $this->applyOrderFilters(
            $request,
            Order::query()->whereNotNull('customer_id'),
            $filters,
        );
        $this->applyCustomerSearchToOrderQuery($orderAggregateQuery, $filters);

        return response()->json([
            'data' => $paginator->getCollection()
                ->map(fn (Customer $customer) => $this->serializeCustomer($customer))
                ->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'total_purchases' => (clone $orderAggregateQuery)->count(),
                'total_spent' => round((float) (clone $orderAggregateQuery)->sum('total'), 2),
                'average_bill' => round((float) (clone $orderAggregateQuery)->avg('total'), 2),
            ],
        ]);
    }

    public function show(Request $request, Customer $customer)
    {
        $filters = $this->validatedFilters($request);
        $query = Customer::query()->whereKey($customer->id);

        if ($this->shouldConstrainCustomersByOrders($request, $filters)) {
            $query->whereHas('orders', fn (Builder $ordersQuery) => $this->applyOrderFilters($request, $ordersQuery, $filters));
        }

        $customer = $query
            ->with([
                'orders' => fn ($ordersQuery) => $this->applyOrderFilters($request, $ordersQuery, $filters)
                    ->with(['branch.restaurant:id,name,kind,logo_url', 'table:id,name', 'payments', 'items.product:id,name'])
                    ->latest('order_date')
                    ->latest('id'),
            ])
            ->withCount(['orders as purchases_count' => fn (Builder $ordersQuery) => $this->applyOrderFilters($request, $ordersQuery, $filters)])
            ->withSum(['orders as total_spent' => fn (Builder $ordersQuery) => $this->applyOrderFilters($request, $ordersQuery, $filters)], 'total')
            ->withAvg(['orders as average_bill' => fn (Builder $ordersQuery) => $this->applyOrderFilters($request, $ordersQuery, $filters)], 'total')
            ->withMax(['orders as highest_bill' => fn (Builder $ordersQuery) => $this->applyOrderFilters($request, $ordersQuery, $filters)], 'total')
            ->withMax(['orders as last_visit_at' => fn (Builder $ordersQuery) => $this->applyOrderFilters($request, $ordersQuery, $filters)], 'order_date')
            ->firstOrFail();

        return response()->json($this->serializeCustomer($customer));
    }

    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'search' => 'nullable|string|max:120',
            'restaurant_id' => 'nullable|integer|exists:restaurants,id',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'min_bill' => 'nullable|numeric|min:0',
            'max_bill' => 'nullable|numeric|min:0|gte:min_bill',
            'date' => 'nullable|date',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'payment_status' => 'nullable|in:unpaid,paid,partial',
            'order_status' => 'nullable|string|max:40',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);
    }

    private function shouldConstrainCustomersByOrders(Request $request, array $filters): bool
    {
        if (! $request->user()?->isPlatformAdmin()) {
            return true;
        }

        foreach (['restaurant_id', 'branch_id', 'min_bill', 'max_bill', 'date', 'from_date', 'to_date', 'payment_status', 'order_status'] as $filter) {
            if (array_key_exists($filter, $filters) && $filters[$filter] !== null && $filters[$filter] !== '') {
                return true;
            }
        }

        return false;
    }

    private function applyOrderFilters(Request $request, $query, array $filters)
    {
        $this->applyOrderAccessScope($request, $query, $filters);

        if (isset($filters['min_bill'])) {
            $query->where('total', '>=', $filters['min_bill']);
        }

        if (isset($filters['max_bill'])) {
            $query->where('total', '<=', $filters['max_bill']);
        }

        if (! empty($filters['date'])) {
            $query->whereDate('order_date', $filters['date']);
        } else {
            if (! empty($filters['from_date'])) {
                $query->whereDate('order_date', '>=', $filters['from_date']);
            }

            if (! empty($filters['to_date'])) {
                $query->whereDate('order_date', '<=', $filters['to_date']);
            }
        }

        if (! empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (! empty($filters['order_status'])) {
            $query->where('status', $filters['order_status']);
        }

        return $query;
    }

    private function applyCustomerSearchToOrderQuery($query, array $filters): void
    {
        if (empty($filters['search'])) {
            return;
        }

        $search = $filters['search'];

        $query->whereHas('customer', function (Builder $customerQuery) use ($search): void {
            $customerQuery
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%");
        });
    }

    private function applyOrderAccessScope(Request $request, $query, array $filters): void
    {
        $user = $request->user();

        if ($user->isPlatformAdmin()) {
            if (! empty($filters['restaurant_id'])) {
                $query->whereHas('branch', fn (Builder $branchQuery) => $branchQuery->where('restaurant_id', $filters['restaurant_id']));
            }

            if (! empty($filters['branch_id'])) {
                $query->where('branch_id', $filters['branch_id']);
            }

            return;
        }

        if ($user->branch_id) {
            $query->where('branch_id', $user->branch_id);

            if (! empty($filters['branch_id'])) {
                $query->where('branch_id', $filters['branch_id']);
            }

            return;
        }

        if ($user->restaurant_id) {
            $query->whereHas('branch', fn (Builder $branchQuery) => $branchQuery->where('restaurant_id', $user->restaurant_id));

            if (! empty($filters['branch_id'])) {
                $query->where('branch_id', $filters['branch_id']);
            }

            return;
        }

        $query->whereRaw('1 = 0');
    }

    private function serializeCustomer(Customer $customer): array
    {
        $orders = $customer->orders ?? collect();
        $branches = $orders
            ->pluck('branch')
            ->filter()
            ->unique('id')
            ->values();
        $restaurants = $branches
            ->pluck('restaurant')
            ->filter()
            ->unique('id')
            ->values();

        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'loyalty_points' => (int) $customer->loyalty_points,
            'phone_verified_at' => $customer->phone_verified_at?->toISOString(),
            'email_verified_at' => $customer->email_verified_at?->toISOString(),
            'created_at' => $customer->created_at?->toISOString(),
            'updated_at' => $customer->updated_at?->toISOString(),
            'purchases_count' => (int) ($customer->purchases_count ?? 0),
            'total_spent' => round((float) ($customer->total_spent ?? 0), 2),
            'average_bill' => round((float) ($customer->average_bill ?? 0), 2),
            'highest_bill' => round((float) ($customer->highest_bill ?? 0), 2),
            'last_visit_at' => $customer->last_visit_at,
            'branches' => $branches->map(fn ($branch) => [
                'id' => $branch->id,
                'name' => $branch->name,
                'restaurant_id' => $branch->restaurant_id,
                'restaurant' => $branch->restaurant ? [
                    'id' => $branch->restaurant->id,
                    'name' => $branch->restaurant->name,
                    'kind' => $branch->restaurant->kind,
                ] : null,
            ]),
            'restaurants' => $restaurants->map(fn ($restaurant) => [
                'id' => $restaurant->id,
                'name' => $restaurant->name,
                'kind' => $restaurant->kind,
            ]),
            'orders' => $orders->map(fn (Order $order) => $this->serializeOrder($order))->values(),
        ];
    }

    private function serializeOrder(Order $order): array
    {
        return [
            'id' => $order->id,
            'branch_id' => $order->branch_id,
            'branch' => $order->branch ? [
                'id' => $order->branch->id,
                'name' => $order->branch->name,
                'restaurant_id' => $order->branch->restaurant_id,
                'restaurant' => $order->branch->restaurant ? [
                    'id' => $order->branch->restaurant->id,
                    'name' => $order->branch->restaurant->name,
                    'kind' => $order->branch->restaurant->kind,
                ] : null,
            ] : null,
            'table' => $order->table ? [
                'id' => $order->table->id,
                'name' => $order->table->name,
            ] : null,
            'order_type' => $order->order_type,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'payment_method' => $order->payment_method,
            'subtotal' => round((float) $order->subtotal, 2),
            'tax' => round((float) $order->tax, 2),
            'discount' => round((float) $order->discount, 2),
            'total' => round((float) $order->total, 2),
            'order_date' => $order->order_date,
            'paid_at' => $order->paid_at,
            'created_at' => $order->created_at?->toISOString(),
            'payments' => $order->payments?->map(fn ($payment) => [
                'id' => $payment->id,
                'method' => $payment->method,
                'amount' => round((float) $payment->amount, 2),
                'created_at' => $payment->created_at?->toISOString(),
            ])->values() ?? [],
            'items' => $order->relationLoaded('items')
                ? $order->items->map(fn ($item) => [
                    'id' => $item->id,
                    'product' => $item->product ? [
                        'id' => $item->product->id,
                        'name' => $item->product->name,
                    ] : null,
                    'quantity' => (int) $item->quantity,
                    'price' => round((float) $item->price, 2),
                    'total' => round((float) $item->total, 2),
                ])->values()
                : [],
        ];
    }
}
