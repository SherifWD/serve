<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TableMobileController extends Controller
{
    public function index(Request $request)
    {
        // Eager load 'orders' relationship
        $tables = Table::with(['orders' => function ($q) {
            $q->where('status', '!=', 'closed'); // Only current orders, if needed
        }])->where('branch_id',auth()->user()->branch_id)->get();

        // You can modify the output as you wish for mobile needs
        return response()->json(['data' => $tables]);
    }

    public function show($id)
    {
        $table = Table::with(['orders.items.product'])->findOrFail($id);
        return response()->json(['data' => $table]);
    }

    public function moveTable(Request $request, $fromTable)
{
    $request->validate([
        'to_table_id' => 'required|integer|exists:tables,id',
    ]);

    $fromTableId = $fromTable;
    $toTableId = $request->input('to_table_id');

    if ($fromTableId == $toTableId) {
        return response()->json(['error' => 'Cannot move to the same table.'], 400);
    }

    // Get all open/running orders for fromTable
    $orders = \App\Models\Order::where('table_id', $fromTableId)
        ->whereIn('status', ['pending', 'open', 'running'])
        ->get();

    if ($orders->isEmpty()) {
        return response()->json(['error' => 'No active orders to move.'], 404);
    }

    // Check if there are orders in the target table
    $targetOrders = \App\Models\Order::where('table_id', $toTableId)
        ->whereIn('status', ['pending', 'open', 'running'])
        ->get();

    if ($targetOrders->isEmpty()) {
        // Simple move: just change table_id
        foreach ($orders as $order) {
            $order->table_id = $toTableId;
            $order->save();
        }
    } else {
        // Merge orders: add items to the target table's first open order
        $targetOrder = $targetOrders->first();
        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                // Update existing item quantity or create new
                $existing = $targetOrder->items()->where('product_id', $item->product_id)->first();
                if ($existing) {
                    $existing->quantity += $item->quantity;
                    $existing->total += $item->total;
                    $existing->save();
                } else {
                    $targetOrder->items()->create([
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'total' => $item->total,
                    ]);
                }
            }
            // Mark source order as 'moved'
            $order->status = 'pending';
            $order->save();
            // Optionally, delete it:
            $order->delete();
        }
        // Optionally update target order's total:
        $targetOrder->total = $targetOrder->items()->sum('total');
        $targetOrder->save();
    }

    return response()->json(['message' => 'Table moved successfully.']);
}

public function sendToCashier(Request $request, $orderId)
{
    $order = Order::with('table')->findOrFail($orderId);
    if ($order->status != 'pending') {
        return response()->json(['error' => 'Order is not open'], 400);
    }
    $order->status = 'cashier';
    $order->save();
    $table = Table::find($order->table->id);
    $table->status = 'cashier';
    $table->save();
    // Optionally, notify cashier via event, etc.
    return response()->json(['order' => $order], 200);
}

public function reopenOrder(Request $request, $orderId)
{
    $order = Order::with('table')->findOrFail($orderId);
    // Only allow reopening if status is 'cashier' (you can adjust this logic)
    if ($order->status !== 'cashier') {
        return response()->json(['error' => 'Only cashier orders can be reopened.'], 400);
    }
    $order->status = 'pending'; // Or 'open', depending on your workflow
    $order->save();
    $table = Table::find($order->table->id);
    $table->status = 'occupied';
    $table->save();

    return response()->json(['order' => $order], 200);
}

public function update(Request $request, $id)
{
    $order = Order::with('items')->findOrFail($id);

    $data = $request->validate([
        'items' => 'required|array|min:1',
        'items.*.product_id' => 'required|integer|exists:products,id',
        'items.*.quantity' => 'required|integer|min:1',
    ]);

    // Update logic: add or update quantities for items
    foreach ($data['items'] as $item) {
        $existing = $order->items()->where('product_id', $item['product_id'])->first();
        $product = \App\Models\Product::findOrFail($item['product_id']);
        if ($existing) {
            $existing->quantity += $item['quantity'];
            $existing->total += $product->price * $item['quantity'];
            $existing->save();
        } else {
            $order->items()->create([
                'product_id' => $item['product_id'],
                'quantity'   => $item['quantity'],
                'price'      => $product->price,
                'total'      => $product->price * $item['quantity'],
            ]);
        }
    }
    // Update order total
    $order->total = $order->items()->sum('total');
    $order->save();

    return response()->json(['order' => $order->load('items.product')]);
}
public function refundOrChangeItem(Request $request, $orderItemId)
{
    $item = OrderItem::findOrFail($orderItemId);
    $action = $request->input('action'); // refund, cancel, change
    $qty = $request->has('quantity') ? (int) $request->input('quantity') : null;
    $note = $request->input('note');
    $userId = auth()->id() ?? 1;
    $restoreStock = $request->boolean('restore_stock', true);

    $before = $item->toArray();
    $product = $item->product;

    $adjustStock = function($product, $qty, $direction = 1) {
        if ($product->recipe) {
            foreach ($product->recipe->ingredients as $ingredient) {
                $pivot = $ingredient->pivot;
                $stockChange = $pivot->quantity * $qty * $direction;
                $ingredient->increment('stock', $stockChange);
            }
        } else {
            $product->increment('stock', $qty * $direction);
        }
    };

    DB::beginTransaction();

    try {
        if ($action === 'refund' || $action === 'cancel') {
            $modQty = $qty ?? $item->quantity;

            if ($modQty < $item->quantity) {
                // Partial refund/cancel: split the item
                $remainingQty = $item->quantity - $modQty;
                $item->quantity = $remainingQty;
                $item->total = $remainingQty * $item->price;
                $item->save();

                $modItem = $item->replicate();
                $modItem->quantity = $modQty;
                $modItem->total = $modQty * $item->price;
                $modItem->status = $action === 'refund' ? 'refunded' : 'cancelled';
                $modItem->refunded_quantity = $modQty;
                $modItem->refunded_amount = $modQty * $item->price;
                $modItem->change_note = $note;
                $modItem->save();

                if ($restoreStock) {
                    $adjustStock($product, $modQty, +1);
                }

                \App\Models\OrderItemHistory::create([
                    'order_item_id' => $modItem->id,
                    'action' => $action,
                    'snapshot_before' => json_encode($before),
                    'snapshot_after' => json_encode($modItem->toArray()),
                    'note' => $note,
                    'user_id' => $userId,
                ]);
                \App\Models\OrderItemHistory::create([
                    'order_item_id' => $item->id,
                    'action' => 'split',
                    'snapshot_before' => json_encode($before),
                    'snapshot_after' => json_encode($item->toArray()),
                    'note' => "Split after $action $modQty",
                    'user_id' => $userId,
                ]);
            } else {
                // Full refund/cancel
                $item->status = $action === 'refund' ? 'refunded' : 'cancelled';
                $item->refunded_quantity = $modQty;
                $item->refunded_amount = $modQty * $item->price;
                $item->change_note = $note;
                $item->save();

                if ($restoreStock) {
                    $adjustStock($product, $modQty, +1);
                }
                \App\Models\OrderItemHistory::create([
                    'order_item_id' => $item->id,
                    'action' => $action,
                    'snapshot_before' => json_encode($before),
                    'snapshot_after' => json_encode($item->toArray()),
                    'note' => $note,
                    'user_id' => $userId,
                ]);
            }
        } elseif ($action === 'change') {
            $oldQty = $item->quantity;
            $newQty = $qty ?? $oldQty;

            if ($newQty < $oldQty) {
                // Reduced quantity, return difference to stock
                $diff = $oldQty - $newQty;
                $item->quantity = $newQty;
                $item->total = $newQty * $item->price;
                $item->status = 'changed';
                $item->change_note = $note;
                $item->save();

                if ($restoreStock) {
                    $adjustStock($product, $diff, +1);
                }
            } else {
                // Increased or same quantity, just update
                $item->quantity = $newQty;
                $item->total = $newQty * $item->price;
                $item->status = 'changed';
                $item->change_note = $note;
                $item->save();
            }

            \App\Models\OrderItemHistory::create([
                'order_item_id' => $item->id,
                'action' => $action,
                'snapshot_before' => json_encode($before),
                'snapshot_after' => json_encode($item->toArray()),
                'note' => $note,
                'user_id' => $userId,
            ]);
        }

        // Update order total for active items only
        $order = $item->order;
        $activeTotal = $order->items()
            ->whereNotIn('status', ['refunded', 'cancelled'])
            ->sum('total');
        $order->total = $activeTotal;
        $order->save();

        $order->load('items.product');
        DB::commit();
        return response()->json([
            'items' => $order->items,
            'order_total' => $activeTotal
        ]);
    } catch (\Exception $ex) {
        DB::rollBack();
        return response()->json(['error' => $ex->getMessage()], 500);
    }
}



public function itemHistory($orderItemId)
{
    $histories = \App\Models\OrderItemHistory::where('order_item_id', $orderItemId)
    ->with('user:id,name')
    ->orderBy('created_at', 'desc')
    ->get();
    Log::info($histories);
    return response()->json(['history' => $histories]);
}

}
