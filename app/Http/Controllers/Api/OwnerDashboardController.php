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

class OwnerDashboardController extends Controller
{
    public function summary(Request $request)
    {
        $viewer = $request->user();
        $branchId = $request->input('branch_id');
        $restaurantId = $viewer->isPlatformAdmin()
            ? $request->input('restaurant_id')
            : $viewer?->restaurant_id;
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
        $totalSales = $totalSalesQuery->sum('total');

        // Total Orders (paid only)
        $ordersQuery = Order::query()->where($paidScope);
        if ($restaurantId) $ordersQuery->whereHas('branch', fn ($q) => $q->where('restaurant_id', $restaurantId));
        if ($branchId) $ordersQuery->where('branch_id', $branchId);
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
            });
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
            ->select('id', 'name', 'quantity', 'unit', 'min_stock')
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
            ]);

        // Recent Orders
        $recentOrdersQuery = Order::with('branch')
            ->orderByDesc('created_at');
        if ($restaurantId) $recentOrdersQuery->whereHas('branch', fn ($q) => $q->where('restaurant_id', $restaurantId));
        if ($branchId) $recentOrdersQuery->where('branch_id', $branchId);
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
            });
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
            ->withSum(['orders as paid_sales' => function ($query) use ($paidScope) {
                $query->where($paidScope);
            }], 'total')
            ->withCount(['orders as paid_orders_count' => function ($query) use ($paidScope) {
                $query->where($paidScope);
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
            'top_products'  => $topProducts,
            'low_stock_items' => $lowInventory,
            'recent_orders' => $recentOrders,
        ]);
    }
}
