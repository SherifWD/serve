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
use App\Models\Expense;
use App\Models\InventoryItem;
use App\Models\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

        $expensesQuery = $this->expenseScopeQuery($restaurantId, $branchId, $dateStart, $dateEnd);
        $totalExpenses = round((float) (clone $expensesQuery)->sum('amount'), 2);
        $netRevenue = round((float) $totalSales - $totalExpenses, 2);
        $expenseByCategory = (clone $expensesQuery)
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->orderByDesc('total')
            ->get()
            ->map(fn (Expense $expense) => [
                'category' => $expense->category,
                'total' => (float) $expense->total,
            ])
            ->values();
        $recentExpenses = (clone $expensesQuery)
            ->with('branch.restaurant:id,name,kind,currency_code')
            ->latest('expense_date')
            ->latest('id')
            ->limit(8)
            ->get()
            ->map(fn (Expense $expense) => $this->expenseRow($expense))
            ->values();
        $employeeRevenue = $this->employeeRevenueRows($restaurantId, $branchId, $dateStart, $dateEnd);

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
            ->whereDate('orders.order_date', '>=', $dateStart->toDateString())
            ->whereDate('orders.order_date', '<=', $dateEnd->toDateString());
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
            ->whereDate('orders.order_date', '>=', $dateStart->toDateString())
            ->whereDate('orders.order_date', '<=', $dateEnd->toDateString());
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
        $branchPerformanceModels = $branchPerformanceQuery->orderByDesc('paid_sales')->limit(6)->get();
        $branchExpensesById = Expense::query()
            ->select('branch_id', DB::raw('SUM(amount) as total'))
            ->whereIn('branch_id', $branchPerformanceModels->pluck('id')->all())
            ->whereDate('expense_date', '>=', $dateStart->toDateString())
            ->whereDate('expense_date', '<=', $dateEnd->toDateString())
            ->groupBy('branch_id')
            ->pluck('total', 'branch_id');
        $branchPerformance = $branchPerformanceModels
            ->map(fn ($branch) => [
                'id' => $branch->id,
                'name' => $branch->name,
                'location' => $branch->location,
                'sales' => (float) ($branch->paid_sales ?? 0),
                'expenses' => (float) ($branchExpensesById[$branch->id] ?? 0),
                'net_revenue' => round((float) ($branch->paid_sales ?? 0) - (float) ($branchExpensesById[$branch->id] ?? 0), 2),
                'orders_count' => $branch->paid_orders_count,
            ]);

        $operations = $this->buildOwnerOperations(
            $restaurantId,
            $branchId,
            $branchPerformance,
            $dateStart,
            $dateEnd
        );

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
            'total_expenses' => $totalExpenses,
            'net_revenue' => $netRevenue,
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
            'expense_by_category' => $expenseByCategory,
            'recent_expenses' => $recentExpenses,
            'employee_revenue' => $employeeRevenue,
            'branch_performance' => $branchPerformance,
            'branch_options' => $branchOptions,
            'selected_branch_id' => $branchId,
            'active_employees' => $operations['active_employees'],
            'branch_details' => $operations['branch_details'],
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
            ->whereDate('order_date', '>=', $dateStart->toDateString())
            ->whereDate('order_date', '<=', $dateEnd->toDateString())
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
        $query->whereDate('order_date', '>=', $start->toDateString())
            ->whereDate('order_date', '<=', $end->toDateString());
    }

    private function expenseScopeQuery(?int $restaurantId, ?int $branchId, Carbon $dateStart, Carbon $dateEnd)
    {
        return Expense::query()
            ->when($restaurantId, fn ($query) => $query->whereHas('branch', fn ($branchQuery) => $branchQuery->where('restaurant_id', $restaurantId)))
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->whereDate('expense_date', '>=', $dateStart->toDateString())
            ->whereDate('expense_date', '<=', $dateEnd->toDateString());
    }

    private function expenseRow(Expense $expense): array
    {
        return [
            'id' => $expense->id,
            'branch_id' => $expense->branch_id,
            'branch_name' => $expense->branch?->name,
            'restaurant_id' => $expense->branch?->restaurant_id,
            'restaurant_name' => $expense->branch?->restaurant?->name,
            'category' => $expense->category,
            'amount' => (float) $expense->amount,
            'description' => $expense->description,
            'expense_date' => optional($expense->expense_date)->toDateString(),
            'created_at' => optional($expense->created_at)->toDateTimeString(),
        ];
    }

    private function employeeRevenueRows(?int $restaurantId, ?int $branchId, Carbon $dateStart, Carbon $dateEnd)
    {
        $query = DB::table('orders')
            ->join('branches', 'branches.id', '=', 'orders.branch_id')
            ->leftJoin('restaurants', 'restaurants.id', '=', 'branches.restaurant_id')
            ->leftJoin('employees', 'employees.id', '=', 'orders.employee_id')
            ->where(function ($query) {
                $query->where('orders.payment_status', 'paid')
                    ->orWhere('orders.status', 'paid');
            })
            ->whereDate('orders.order_date', '>=', $dateStart->toDateString())
            ->whereDate('orders.order_date', '<=', $dateEnd->toDateString())
            ->when($restaurantId, fn ($query) => $query->where('branches.restaurant_id', $restaurantId))
            ->when($branchId, fn ($query) => $query->where('orders.branch_id', $branchId))
            ->select([
                'orders.employee_id',
                'employees.name as employee_name',
                'employees.position',
                'orders.branch_id',
                'branches.name as branch_name',
                'restaurants.name as restaurant_name',
                DB::raw('COUNT(orders.id) as orders_count'),
                DB::raw('SUM(orders.total) as revenue'),
            ])
            ->groupBy(
                'orders.employee_id',
                'employees.name',
                'employees.position',
                'orders.branch_id',
                'branches.name',
                'restaurants.name',
            )
            ->orderByDesc('revenue')
            ->limit(25)
            ->get();

        return $query->map(fn ($row) => [
            'employee_id' => $row->employee_id ? (int) $row->employee_id : null,
            'employee_name' => $row->employee_name ?: 'Unassigned',
            'position' => $row->position,
            'restaurant_name' => $row->restaurant_name,
            'branch_id' => (int) $row->branch_id,
            'branch_name' => $row->branch_name,
            'orders_count' => (int) $row->orders_count,
            'revenue' => (float) $row->revenue,
            'average_order' => (int) $row->orders_count > 0
                ? round((float) $row->revenue / (int) $row->orders_count, 2)
                : 0,
        ])->values();
    }

    private function buildOwnerOperations(?int $restaurantId, ?int $branchId, $branchPerformance, Carbon $dateStart, Carbon $dateEnd): array
    {
        $branchIds = collect($branchPerformance)
            ->pluck('id')
            ->filter()
            ->values();

        $branches = Branch::query()
            ->when($restaurantId, fn ($query) => $query->where('restaurant_id', $restaurantId))
            ->when($branchId, fn ($query) => $query->whereKey($branchId))
            ->when($branchIds->isNotEmpty(), fn ($query) => $query->whereIn('id', $branchIds))
            ->orderBy('name')
            ->get(['id', 'name', 'location', 'restaurant_id']);

        $performanceByBranch = collect($branchPerformance)->keyBy('id');

        return [
            'active_employees' => $this->employeeRows($restaurantId, $branchId, onlyActive: true),
            'branch_details' => $branches
                ->map(function (Branch $branch) use ($performanceByBranch, $dateStart, $dateEnd) {
                    $performance = $performanceByBranch->get($branch->id, []);
                    $employees = collect($this->employeeRows(null, (int) $branch->id, onlyActive: false));
                    $tables = Table::query()
                        ->where('branch_id', $branch->id)
                        ->orderBy('name')
                        ->get(['id', 'branch_id', 'name', 'seats', 'status'])
                        ->map(fn (Table $table) => [
                            'id' => $table->id,
                            'name' => $table->name,
                            'seats' => (int) ($table->seats ?? 0),
                            'status' => $table->status,
                        ])
                        ->values();

                    $orders = Order::query()
                        ->with([
                            'branch:id,name,location',
                            'table:id,name,status',
                            'employee.user:id,name,role',
                            'items:id,order_id,product_id,quantity,total,status,kds_status,refunded_quantity,refunded_amount',
                            'items.product:id,name',
                            'statusLogs.user:id,name,role',
                        ])
                        ->where('branch_id', $branch->id)
                        ->whereDate('order_date', '>=', $dateStart->toDateString())
                        ->whereDate('order_date', '<=', $dateEnd->toDateString())
                        ->orderByDesc('created_at')
                        ->limit(10)
                        ->get()
                        ->map(fn (Order $order) => $this->ownerOrderRow($order))
                        ->values();

                    $returnedOrderQuery = Order::query()
                        ->where('branch_id', $branch->id)
                        ->whereDate('order_date', '>=', $dateStart->toDateString())
                        ->whereDate('order_date', '<=', $dateEnd->toDateString())
                        ->where(function ($query) {
                            $query->where('status', 'refunded')
                                ->orWhereHas('items', fn ($itemQuery) => $this->applyReturnedItemScope($itemQuery));
                        });
                    $returnedOrdersCount = (clone $returnedOrderQuery)->count();
                    $returnedOrders = $returnedOrderQuery
                        ->with([
                            'branch:id,name,location',
                            'table:id,name,status',
                            'employee.user:id,name,role',
                            'items:id,order_id,product_id,quantity,total,status,kds_status,refunded_quantity,refunded_amount',
                            'items.product:id,name',
                            'statusLogs.user:id,name,role',
                        ])
                        ->orderByDesc('created_at')
                        ->limit(10)
                        ->get()
                        ->map(fn (Order $order) => $this->ownerOrderRow($order))
                        ->values();

                    return [
                        'id' => $branch->id,
                        'name' => $branch->name,
                        'location' => $branch->location,
                        'sales' => (float) ($performance['sales'] ?? 0),
                        'expenses' => (float) ($performance['expenses'] ?? 0),
                        'net_revenue' => (float) ($performance['net_revenue'] ?? (($performance['sales'] ?? 0) - ($performance['expenses'] ?? 0))),
                        'orders_count' => (int) ($performance['orders_count'] ?? 0),
                        'returned_orders_count' => $returnedOrdersCount,
                        'employees' => $employees->values(),
                        'active_employees' => $employees->where('active', true)->values(),
                        'kitchen_shift' => $employees
                            ->filter(fn (array $employee) => $this->isKitchenEmployee($employee))
                            ->values(),
                        'tables' => $tables,
                        'orders' => $orders,
                        'returned_orders' => $returnedOrders,
                    ];
                })
                ->values(),
        ];
    }

    private function employeeRows(?int $restaurantId, ?int $branchId, bool $onlyActive): array
    {
        $employees = Employee::query()
            ->with([
                'branch:id,name',
                'user:id,name,email,role,branch_id',
                'user.types:id,name',
            ])
            ->when($restaurantId, fn ($query) => $query->whereHas('branch', fn ($branchQuery) => $branchQuery->where('restaurant_id', $restaurantId)))
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->orderBy('position')
            ->orderBy('name')
            ->get(['id', 'user_id', 'branch_id', 'name', 'position']);

        $attendanceByEmployee = $this->openAttendanceByEmployee($employees->pluck('id')->filter()->values());
        $shiftByUser = $this->openShiftByUser($employees->pluck('user_id')->filter()->values());

        return $employees
            ->map(function (Employee $employee) use ($attendanceByEmployee, $shiftByUser) {
                $attendance = $attendanceByEmployee->get($employee->id);
                $shift = $employee->user_id ? $shiftByUser->get($employee->user_id) : null;
                $active = $attendance !== null || $shift !== null;
                $type = $employee->user?->types?->pluck('name')?->first();

                return [
                    'id' => $employee->id,
                    'user_id' => $employee->user_id,
                    'name' => $employee->name ?: ($employee->user?->name ?? "Employee #{$employee->id}"),
                    'position' => $employee->position,
                    'role' => $employee->user?->role,
                    'type' => $type,
                    'branch_id' => $employee->branch_id,
                    'branch_name' => $employee->branch?->name,
                    'active' => $active,
                    'active_source' => $shift !== null ? 'shift' : ($attendance !== null ? 'attendance' : null),
                    'check_in' => $attendance?->check_in,
                    'shift_start' => $shift?->shift_start,
                ];
            })
            ->filter(fn (array $employee) => !$onlyActive || $employee['active'])
            ->values()
            ->all();
    }

    private function openAttendanceByEmployee($employeeIds)
    {
        if ($employeeIds->isEmpty()) {
            return collect();
        }

        $table = $this->attendanceTableName();
        if (!$table) {
            return collect();
        }

        return DB::table($table)
            ->whereIn('employee_id', $employeeIds)
            ->whereDate('date', Carbon::today()->toDateString())
            ->whereNotNull('check_in')
            ->whereNull('check_out')
            ->orderByDesc('check_in')
            ->get()
            ->keyBy('employee_id');
    }

    private function openShiftByUser($userIds)
    {
        if ($userIds->isEmpty() || !Schema::hasTable('staff_shifts')) {
            return collect();
        }

        $now = Carbon::now();

        return DB::table('staff_shifts')
            ->whereIn('user_id', $userIds)
            ->where('shift_start', '<=', $now)
            ->where(function ($query) use ($now) {
                $query->whereNull('shift_end')
                    ->orWhere('shift_end', '>=', $now);
            })
            ->where('is_closed', false)
            ->orderByDesc('shift_start')
            ->get()
            ->keyBy('user_id');
    }

    private function attendanceTableName(): ?string
    {
        if (Schema::hasTable('attendance')) {
            return 'attendance';
        }

        if (Schema::hasTable('attendances')) {
            return 'attendances';
        }

        return null;
    }

    private function ownerOrderRow(Order $order): array
    {
        $returnedItems = $order->items
            ->filter(fn (OrderItem $item) => $this->isReturnedItem($item))
            ->values();
        $cashierLog = $order->statusLogs
            ->filter(fn ($log) => in_array($log->status, ['paid', 'cashier', 'closed'], true))
            ->sortByDesc('created_at')
            ->first();
        $refundedByNames = DB::table('refunds')
            ->join('order_items', 'order_items.id', '=', 'refunds.order_item_id')
            ->leftJoin('users', 'users.id', '=', 'refunds.refunded_by')
            ->where('order_items.order_id', $order->id)
            ->pluck('users.name')
            ->filter()
            ->unique()
            ->values()
            ->all();

        return [
            'id' => $order->id,
            'branch_id' => $order->branch_id,
            'branch_name' => $order->branch?->name,
            'table_id' => $order->table_id,
            'table_name' => $order->table?->name,
            'order_type' => $order->order_type,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'total' => (float) $order->total,
            'order_date' => $order->order_date,
            'created_at' => optional($order->created_at)->toDateTimeString(),
            'waiter_name' => $order->employee?->name ?: $order->employee?->user?->name,
            'cashier_name' => $cashierLog?->user?->name,
            'returned_by' => $refundedByNames,
            'returned_items_count' => $returnedItems->count(),
            'returned_amount' => round((float) $returnedItems->sum(fn (OrderItem $item) => $item->refunded_amount ?? $item->total), 2),
            'items' => $order->items
                ->map(fn (OrderItem $item) => [
                    'id' => $item->id,
                    'name' => $item->product?->name ?? 'Item',
                    'quantity' => (int) $item->quantity,
                    'total' => (float) $item->total,
                    'status' => $item->status,
                    'kds_status' => $item->kds_status,
                    'refunded_quantity' => (int) ($item->refunded_quantity ?? 0),
                ])
                ->values(),
        ];
    }

    private function isReturnedItem(OrderItem $item): bool
    {
        return in_array($item->status, ['returned', 'refunded', 'canceled', 'cancelled'], true)
            || in_array($item->kds_status, ['returned', 'refunded', 'canceled', 'cancelled'], true)
            || (int) ($item->refunded_quantity ?? 0) > 0;
    }

    private function applyReturnedItemScope($query): void
    {
        $query->where(function ($itemQuery) {
            $itemQuery->whereIn('status', ['returned', 'refunded', 'canceled', 'cancelled'])
                ->orWhereIn('kds_status', ['returned', 'refunded', 'canceled', 'cancelled'])
                ->orWhere('refunded_quantity', '>', 0);
        });
    }

    private function isKitchenEmployee(array $employee): bool
    {
        return str_contains(strtolower((string) ($employee['type'] ?? '')), 'kitchen')
            || str_contains(strtolower((string) ($employee['position'] ?? '')), 'kitchen');
    }
}
