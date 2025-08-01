<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KDSController extends Controller
{
    /**
     * Get all orders (with items) in this branch that are still pending or preparing.
     */
    public function getActiveOrders(Request $request)
    {
        $user = auth()->user();
    $branchId = $user->branch_id;

    $orders = Order::with([
        'table', // Make sure you have this relationship
        'items.product', // Load product relation on each item
        'employee', // Optional: waiter/employee relation
        'items.modifier.modifier'
    ])
    ->where('branch_id', $branchId)
    ->whereIn('status', ['pending', 'preparing'])
    ->whereNot('status','refunded')
    ->get();
        Log::info($orders);
        Log::info($branchId);

        return response()->json(['data' => $orders]);
    }

    /**
     * Set entire order status (pending -> preparing OR preparing -> prepared)
     */
    public function setOrderStatus(Request $request, $orderId)
    {
        $order = Order::with('items')->where('branch_id', Auth::user()->branch_id)->findOrFail($orderId);

        $targetStatus = $request->input('status'); // 'preparing' or 'prepared'

        if (!in_array($targetStatus, ['preparing', 'prepared'])) {
            return response()->json(['error' => 'Invalid target status'], 422);
        }

        // Only allow correct transitions
        if ($order->status === 'pending' && $targetStatus === 'preparing') {
            $order->status = 'preparing';
        } elseif ($order->status === 'preparing' && $targetStatus === 'prepared') {
            $order->status = 'prepared';
        } else {
            return response()->json(['error' => 'Invalid status transition'], 422);
        }

        DB::transaction(function () use ($order, $targetStatus) {
            $order->save();
            // Update all items to match new status
            foreach ($order->items as $item) {
                if (
                    ($item->status === 'pending' && $targetStatus === 'preparing') ||
                    ($item->status === 'preparing' && $targetStatus === 'prepared')
                ) {
                    $item->status = $targetStatus;
                    $item->save();
                }
            }
        });

        return response()->json(['message' => 'Order status updated', 'order' => $order->fresh('items')]);
    }

    /**
     * Set single order item status (pending -> preparing OR preparing -> prepared)
     */
    public function setOrderItemStatus(Request $request, $orderItemId)
    {
        $item = OrderItem::with('order')->findOrFail($orderItemId);

        // Ensure the user is in the correct branch
        if ($item->order->branch_id !== Auth::user()->branch_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $targetStatus = $request->input('status'); // 'preparing' or 'prepared'

        if (!in_array($targetStatus, ['preparing', 'prepared'])) {
            return response()->json(['error' => 'Invalid target status'], 422);
        }

        // Only allow correct transitions
        if ($item->status === 'pending' && $targetStatus === 'preparing') {
            $item->status = 'preparing';
        } elseif ($item->status === 'preparing' && $targetStatus === 'prepared') {
            $item->status = 'prepared';
        } else {
            return response()->json(['error' => 'Invalid status transition'], 422);
        }

        $item->save();

        // Optionally: If all items are now prepared, auto-mark order as prepared
        if ($targetStatus === 'prepared') {
            $order = $item->order->fresh('items');
            if ($order->items->every(fn($i) => $i->status === 'prepared')) {
                $order->status = 'prepared';
                $order->save();
            }
        }

        return response()->json(['message' => 'Order item status updated', 'item' => $item]);
    }
}
