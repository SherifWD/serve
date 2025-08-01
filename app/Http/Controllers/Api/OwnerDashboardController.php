<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;
use App\Models\Employee;
use App\Models\Ingredient;
use Illuminate\Support\Facades\DB;

class OwnerDashboardController extends Controller
{
    public function summary(Request $request)
    {
        // Optionally filter by branch
        $branchId = $request->input('branch_id');

        // Total Sales (paid orders only)
        $totalSalesQuery = Order::where('status', 'paid');
        if ($branchId) $totalSalesQuery->where('branch_id', $branchId);
        $totalSales = $totalSalesQuery->sum('total');

        // Total Orders (paid only)
        $ordersQuery = Order::where('status', 'paid');
        if ($branchId) $ordersQuery->where('branch_id', $branchId);
        $totalOrders = $ordersQuery->count();

        // Average Order Value
        $avgOrderValue = $totalOrders > 0 ? round($totalSales / $totalOrders, 2) : 0;

        // Products & Employees count (by branch if you want)
        $products = $branchId
            ? Product::where('branch_id', $branchId)->count()
            : Product::count();
        $employees = Employee::count();

        // Top 5 Products (by quantity sold in paid orders)
        $topProductsQuery = DB::table('order_items')
            ->select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', 'paid');
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
        $lowInventory = Ingredient::where('stock', '<', 10)
            ->orderBy('stock', 'asc')
            ->get(['id', 'name', 'stock', 'unit']);

        // Recent Orders
        $recentOrdersQuery = Order::with('branch')
            ->orderByDesc('created_at');
        if ($branchId) $recentOrdersQuery->where('branch_id', $branchId);
        $recentOrders = $recentOrdersQuery->limit(5)->get(['id', 'branch_id', 'total', 'status', 'created_at']);

        return response()->json([
            'total_sales'   => $totalSales,
            'orders_count'  => $totalOrders,
            'avg_order_value' => $avgOrderValue,
            'product_count' => $products,
            'employee_count'=> $employees,
            'top_products'  => $topProducts,
            'low_stock_items' => $lowInventory,
            'recent_orders' => $recentOrders,
        ]);
    }
}
