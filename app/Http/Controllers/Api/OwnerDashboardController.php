<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\Employee;
use App\Models\InventoryItem;
use App\Models\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class OwnerDashboardController extends Controller
{
    public function summary(Request $request)
    {
        $scope = $this->resolveDashboardScope($request);
        if ($scope['error']) {
            return $scope['error'];
        }
        $branchId = $scope['branch_id'];
        $restaurantId = $scope['restaurant_id'];
        $branchOptions = $scope['branch_options'];
        $dateRange = $this->resolveDateRange($request);
        $dateStart = $dateRange['start'];
        $dateEnd = $dateRange['end'];
        $paidScope = function ($query) {
            $query->where(function ($inner) {
                $inner->where('payment_status', 'paid')
                    ->orWhere('status', 'paid');
            });
        };

        // Total Sales (paid orders only)
        $totalSalesQuery = Order::query()->where($paidScope);
        if ($restaurantId) $totalSalesQuery->whereHas('branch', fn ($q) => $q->where('restaurant_id', $restaurantId));
        if ($branchId) $totalSalesQuery->where('branch_id', $branchId);
        $this->applyOrderDateRange($totalSalesQuery, $dateStart, $dateEnd);
        $totalSales = $totalSalesQuery->sum('total');

        // Total Orders (paid only)
        $ordersQuery = Order::query()->where($paidScope);
        if ($restaurantId) $ordersQuery->whereHas('branch', fn ($q) => $q->where('restaurant_id', $restaurantId));
        if ($branchId) $ordersQuery->where('branch_id', $branchId);
        $this->applyOrderDateRange($ordersQuery, $dateStart, $dateEnd);
        $totalOrders = $ordersQuery->count();

        // Average Order Value
        $avgOrderValue = $totalOrders > 0 ? round($totalSales / $totalOrders, 2) : 0;

        // Products & Employees count (by branch if you want)
        $products = Product::query()
            ->when($restaurantId, fn ($q) => $q->whereHas('branch', fn ($branchQuery) => $branchQuery->where('restaurant_id', $restaurantId)))
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->count();
        $employeesQuery = Employee::query();
        if ($restaurantId) {
            $employeesQuery->whereHas('branch', fn ($q) => $q->where('restaurant_id', $restaurantId));
        }
        if ($branchId) {
            $employeesQuery->where('branch_id', $branchId);
        }
        $employees = $employeesQuery->count();

        // Top 5 Products (by quantity sold in paid orders)
        $topProductsQuery = DB::table('order_items')
            ->select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where(function ($query) {
                $query->where('orders.payment_status', 'paid')
                    ->orWhere('orders.status', 'paid');
            })
            ->whereBetween('orders.order_date', [$dateStart->toDateString(), $dateEnd->toDateString()]);
        if ($restaurantId) $topProductsQuery->join('branches', 'branches.id', '=', 'orders.branch_id')
            ->where('branches.restaurant_id', $restaurantId);
        if ($branchId) $topProductsQuery->where('orders.branch_id', $branchId);
        $topProducts = $topProductsQuery
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                $product = Product::find($item->product_id);
                return [
                    'name' => $product?->name ?? 'Unknown',
                    'quantity' => $item->total_sold, // Changed to 'quantity' for clarity
                ];
            });

        // Low Inventory Ingredients (< 10 in stock)
        $lowInventoryQuery = InventoryItem::query()
            ->select('id', 'branch_id', 'name', 'quantity', 'unit', 'min_stock')
            ->with('branch:id,name')
            ->whereColumn('quantity', '<=', 'min_stock')
            ->orderBy('quantity', 'asc');
        if ($restaurantId) {
            $lowInventoryQuery->whereHas('branch', fn ($q) => $q->where('restaurant_id', $restaurantId));
        }
        if ($branchId) {
            $lowInventoryQuery->where('branch_id', $branchId);
        }
        $lowInventory = $lowInventoryQuery->limit(10)->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'stock' => (float) $item->quantity,
                'unit' => $item->unit,
                'branch_id' => $item->branch_id,
                'branch_name' => $item->branch?->name,
            ]);

        // Recent Orders
        $recentOrdersQuery = Order::with('branch')
            ->orderByDesc('created_at');
        if ($restaurantId) $recentOrdersQuery->whereHas('branch', fn ($q) => $q->where('restaurant_id', $restaurantId));
        if ($branchId) $recentOrdersQuery->where('branch_id', $branchId);
        $this->applyOrderDateRange($recentOrdersQuery, $dateStart, $dateEnd);
        $recentOrders = $recentOrdersQuery->limit(5)->get(['id', 'branch_id', 'total', 'status', 'created_at']);

        $activeTablesQuery = Table::query();
        if ($restaurantId) $activeTablesQuery->whereHas('branch', fn ($q) => $q->where('restaurant_id', $restaurantId));
        if ($branchId) $activeTablesQuery->where('branch_id', $branchId);
        $activeTables = $activeTablesQuery->where('status', 'occupied')->count();

        $cashierQueueQuery = Order::query()->where('status', 'cashier');
        if ($restaurantId) $cashierQueueQuery->whereHas('branch', fn ($q) => $q->where('restaurant_id', $restaurantId));
        if ($branchId) $cashierQueueQuery->where('branch_id', $branchId);
        $cashierQueue = $cashierQueueQuery->count();

        $kdsBacklogQuery = OrderItem::query()
            ->whereIn('kds_status', ['queued', 'preparing'])
            ->whereHas('order', fn ($q) => $q->whereIn('status', ['pending', 'open', 'running']));
        if ($restaurantId) $kdsBacklogQuery->whereHas('order.branch', fn ($q) => $q->where('restaurant_id', $restaurantId));
        if ($branchId) $kdsBacklogQuery->whereHas('order', fn ($q) => $q->where('branch_id', $branchId));
        $kdsBacklog = $kdsBacklogQuery->count();

        $paymentMixQuery = Payment::query()
            ->select('method', DB::raw('SUM(amount) as total'))
            ->join('orders', 'orders.id', '=', 'payments.order_id')
            ->where(function ($query) {
                $query->where('orders.payment_status', 'paid')
                    ->orWhere('orders.status', 'paid');
            })
            ->whereBetween('orders.order_date', [$dateStart->toDateString(), $dateEnd->toDateString()]);
        if ($restaurantId) $paymentMixQuery->join('branches', 'branches.id', '=', 'orders.branch_id')
            ->where('branches.restaurant_id', $restaurantId);
        if ($branchId) $paymentMixQuery->where('orders.branch_id', $branchId);
        $paymentMix = $paymentMixQuery
            ->groupBy('method')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($payment) => [
                'method' => $payment->method,
                'total' => (float) $payment->total,
            ]);

        $branchPerformanceQuery = Branch::query()
            ->when($restaurantId, fn ($q) => $q->where('restaurant_id', $restaurantId))
            ->withSum(['orders as paid_sales' => function ($query) use ($paidScope, $dateStart, $dateEnd) {
                $query->where($paidScope);
                $this->applyOrderDateRange($query, $dateStart, $dateEnd);
            }], 'total')
            ->withCount(['orders as paid_orders_count' => function ($query) use ($paidScope, $dateStart, $dateEnd) {
                $query->where($paidScope);
                $this->applyOrderDateRange($query, $dateStart, $dateEnd);
            }]);
        if ($branchId) $branchPerformanceQuery->where('id', $branchId);
        $branchPerformance = $branchPerformanceQuery->orderByDesc('paid_sales')->limit(6)->get()
            ->map(fn ($branch) => [
                'id' => $branch->id,
                'name' => $branch->name,
                'location' => $branch->location,
                'sales' => (float) ($branch->paid_sales ?? 0),
                'orders_count' => $branch->paid_orders_count,
            ]);

        $restaurantsCount = Restaurant::query()->count();
        $branchesCountQuery = Branch::query();
        if ($restaurantId) {
            $branchesCountQuery->where('restaurant_id', $restaurantId);
        }
        $branchesCount = $branchesCountQuery->count();

        $loyaltyMembersQuery = Customer::query()->where('loyalty_points', '>', 0);
        if ($restaurantId || $branchId) {
            $loyaltyMembersQuery->whereHas('orders', function ($query) use ($restaurantId, $branchId) {
                if ($restaurantId) {
                    $query->whereHas('branch', fn ($branchQuery) => $branchQuery->where('restaurant_id', $restaurantId));
                }
                if ($branchId) {
                    $query->where('branch_id', $branchId);
                }
            });
        }
        $loyaltyMembers = $loyaltyMembersQuery->distinct('customers.id')->count('customers.id');

        return response()->json([
            'total_sales'   => $totalSales,
            'orders_count'  => $totalOrders,
            'avg_order_value' => $avgOrderValue,
            'restaurant_count' => $restaurantsCount,
            'branch_count' => $branchesCount,
            'product_count' => $products,
            'employee_count'=> $employees,
            'active_tables' => $activeTables,
            'cashier_queue' => $cashierQueue,
            'kds_backlog' => $kdsBacklog,
            'loyalty_members' => $loyaltyMembers,
            'payment_mix' => $paymentMix,
            'branch_performance' => $branchPerformance,
            'branch_options' => $branchOptions,
            'selected_branch_id' => $branchId,
            'top_products'  => $topProducts,
            'low_stock_items' => $lowInventory,
            'recent_orders' => $recentOrders,
            'date_range' => [
                'preset' => $dateRange['preset'],
                'start_date' => $dateStart->toDateString(),
                'end_date' => $dateEnd->toDateString(),
            ],
        ]);
    }

    public function receipt(Request $request)
    {
        $scope = $this->resolveDashboardScope($request);
        if ($scope['error']) {
            return $scope['error'];
        }
        $branchId = $scope['branch_id'];
        $restaurantId = $scope['restaurant_id'];
        $dateRange = $this->resolveDateRange($request);
        $dateStart = $dateRange['start'];
        $dateEnd = $dateRange['end'];

        $orders = Order::query()
            ->with(['branch.restaurant', 'table', 'customer', 'items.product', 'items.modifiers.modifier', 'payments'])
            ->where(function ($query) {
                $query->where('payment_status', 'paid')
                    ->orWhere('status', 'paid');
            })
            ->whereBetween('order_date', [$dateStart->toDateString(), $dateEnd->toDateString()])
            ->when($restaurantId, fn ($query) => $query->whereHas('branch', fn ($branchQuery) => $branchQuery->where('restaurant_id', $restaurantId)))
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->orderBy('order_date')
            ->orderBy('id')
            ->get();

        $productSummary = $orders
            ->flatMap(fn ($order) => $order->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'name' => $item->product?->name ?? 'Unknown',
                'quantity' => (int) $item->quantity,
                'total' => (float) $item->total,
                'returned' => in_array($item->status, ['returned', 'refunded'], true) ? (int) $item->quantity : 0,
            ]))
            ->groupBy('product_id')
            ->map(function ($items) {
                return [
                    'name' => $items->first()['name'],
                    'quantity' => $items->sum('quantity'),
                    'returned' => $items->sum('returned'),
                    'total' => round($items->sum('total'), 2),
                ];
            })
            ->sortByDesc('total')
            ->values();

        $report = [
            'start_date' => $dateStart->toDateString(),
            'end_date' => $dateEnd->toDateString(),
            'order_count' => $orders->count(),
            'total_sales' => round((float) $orders->sum('total'), 2),
            'paid_amount' => round((float) $orders->flatMap(fn ($order) => $order->payments)->sum('amount'), 2),
            'product_summary' => $productSummary,
        ];

        @ini_set('memory_limit', '512M');

        $pdf = \PDF::loadView('receipts.owner-period', compact('orders', 'report'));

        return $pdf->download("owner_receipt_{$report['start_date']}_{$report['end_date']}.pdf");
    }

    private function resolveDashboardScope(Request $request): array
    {
        $viewer = $request->user();
        $restaurantId = $viewer?->isPlatformAdmin()
            ? $request->input('restaurant_id')
            : $viewer?->restaurant_id;

        $accessibleBranchesQuery = Branch::query()
            ->when($restaurantId, fn ($query) => $query->where('restaurant_id', $restaurantId))
            ->when($viewer?->branch_id, fn ($query) => $query->whereKey($viewer->branch_id));

        $branchOptions = (clone $accessibleBranchesQuery)
            ->orderBy('name')
            ->get(['id', 'name', 'location', 'restaurant_id'])
            ->map(fn ($branch) => [
                'id' => $branch->id,
                'name' => $branch->name,
                'location' => $branch->location,
                'restaurant_id' => $branch->restaurant_id,
            ])
            ->values();

        $requestedBranchId = $request->filled('branch_id')
            ? (int) $request->input('branch_id')
            : null;
        $branchId = null;

        if ($viewer?->branch_id) {
            $branchId = (int) $viewer->branch_id;
        } elseif ($requestedBranchId) {
            $isAccessible = (clone $accessibleBranchesQuery)
                ->whereKey($requestedBranchId)
                ->exists();

            if (!$isAccessible) {
                return [
                    'error' => response()->json(['error' => 'Branch is not accessible.'], 403),
                    'restaurant_id' => $restaurantId,
                    'branch_id' => null,
                    'branch_options' => $branchOptions,
                ];
            }

            $branchId = $requestedBranchId;
        }

        return [
            'error' => null,
            'restaurant_id' => $restaurantId,
            'branch_id' => $branchId,
            'branch_options' => $branchOptions,
        ];
    }

    private function resolveDateRange(Request $request): array
    {
        $preset = strtolower((string) $request->input('preset', 'today'));
        $startInput = $request->input('start_date', $request->input('from'));
        $endInput = $request->input('end_date', $request->input('to'));

        if ($startInput || $endInput) {
            $start = $startInput ? Carbon::parse($startInput) : Carbon::today();
            $end = $endInput ? Carbon::parse($endInput) : $start;
            $preset = 'custom';
        } else {
            $today = Carbon::today();
            [$start, $end] = match ($preset) {
                'week' => [$today->copy()->subDays(6), $today],
                'month' => [$today->copy()->startOfMonth(), $today],
                default => [$today, $today],
            };
        }

        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
        }

        return [
            'preset' => $preset,
            'start' => $start->startOfDay(),
            'end' => $end->endOfDay(),
        ];
    }

    private function applyOrderDateRange($query, Carbon $start, Carbon $end): void
    {
        $query->whereBetween('order_date', [$start->toDateString(), $end->toDateString()]);
    }
}
