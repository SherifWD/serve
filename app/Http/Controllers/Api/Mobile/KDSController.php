<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Events\KDSItemStatusUpdated;
use App\Events\OrderReadyForCashier;
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
    
// app/Http/Controllers/Api/Mobile/KDSController.php

public function getActiveOrders(Request $request)
{
    $branchId = $request->user()->branch_id;

    // normalized list for both US/UK spellings, just in case
    $nonActiveItemStatuses = ['canceled','cancelled','refunded'];

    $itemScope = function ($q) use ($nonActiveItemStatuses) {
        $q->whereNotIn('status', $nonActiveItemStatuses)
          ->whereIn('kds_status', ['queued','preparing','ready'])
          ->with(['product', 'modifiers.modifier']); // eager-load for UI badges
    };

    $orders = \App\Models\Order::query()
        ->where('branch_id', $branchId)
        ->whereNotNull('kds_sent_at')
        ->whereIn('status', ['pending','open']) // KDS shows not-yet-closed orders
        ->whereHas('items', $itemScope)         // only if it has visible items
        ->with(['table','items' => $itemScope]) // but only those filtered items
        ->orderBy('order_date', 'asc')
        ->get()
        ->map(function($o){
            // Optional: flatten a few fields the KDS page expects
            return [
                'id'         => $o->id,
                'table_name' => optional($o->table)->name,
                'waiter'     => optional($o->employee)->name ?? '', // if you have a relation
                'kds_sent_at'=> $o->kds_sent_at,
                'created_at' => $o->created_at,
                'items'      => $o->items->map(function($i){
                    return [
                        'id'         => $i->id,
                        'quantity'   => $i->quantity,
                        'product'    => ['name' => optional($i->product)->name], // keep it an object
                        'note'       => $i->note,
                        'status'     => $i->status,       // business status
                        'kds_status' => $i->kds_status,   // UI status
                        'modifiers'  => $i->modifiers->map(function($m){
                            return ['name' => $m->modifier->name ?? ($m->raw_modifier ?? '')];
                        })->values(),
                    ];
                })->values(),
            ];
        });

    return response()->json(['data' => $orders]);
}

public function setOrderItemStatus(Request $request, OrderItem $item)
{
    $data = $request->validate([
        'status' => 'required|in:queued,preparing,ready,canceled,refunded,served',
    ]);

    // Must be same branch
    if ($request->user()->branch_id !== $item->order->branch_id) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    $item->kds_status = $data['status'];
    $item->save();

    event(new KDSItemStatusUpdated($item));

    // If all non-canceled/non-refunded items are ready/served → broadcast ready_for_cashier
    $order = $item->order()->with('items')->first();
    $allDone = $order->items
        ->filter(fn($i) => !in_array($i->kds_status, ['canceled','refunded']))
        ->every(fn($i) => in_array($i->kds_status, ['ready','served']));

    if ($allDone) {
        event(new OrderReadyForCashier($order));
    }

    return response()->json(['item' => $item]);
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
    
}
