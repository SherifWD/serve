<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Enums\OrderStatus;
use App\Enums\TableStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TableMobileController extends Controller
{
    public function index(Request $request)
    {
        $branchId = $request->user()->branch_id;

        $tables = Table::where('branch_id', $branchId)
            ->with([
                'orders' => fn($q) => $q->where('status','!=',OrderStatus::CLOSED),
                'orders.items.product',
                'orders.items.answers.choice.question',
                'orders.items.modifiers.modifier',
            ])->get();

        return response()->json(['data' => $tables]);
    }

    public function show(Request $request, $id)
    {
        $branchId = $request->user()->branch_id;

        $table = Table::where('branch_id', $branchId)
            ->with([
                'orders.items.product',
                'orders.items.answers.choice.question',
                'orders.items.modifiers.modifier',
            ])->findOrFail($id);

        return response()->json(['data' => $table]);
    }

    public function moveTable(Request $request, $fromTable)
    {
        $data = $request->validate([
            'to_table_id' => 'required|integer|exists:tables,id',
        ]);
        $user = $request->user();

        return DB::transaction(function () use ($data, $fromTable, $user) {
            $from = Table::where('branch_id', $user->branch_id)->lockForUpdate()->findOrFail($fromTable);
            $to   = Table::where('branch_id', $user->branch_id)->lockForUpdate()->findOrFail($data['to_table_id']);

            if ($from->id === $to->id) {
                return response()->json(['error' => 'Cannot move to the same table.'], 400);
            }

            $orders = Order::with(['items.answers','items.modifiers'])
                ->where('table_id', $from->id)
                ->whereIn('status', [OrderStatus::PENDING, OrderStatus::OPEN, OrderStatus::RUNNING])
                ->lockForUpdate()
                ->get();

            if ($orders->isEmpty()) {
                return response()->json(['error' => 'No active orders to move.'], 404);
            }

            // Ensure a target open order
            $target = Order::where('table_id', $to->id)
                ->whereIn('status', [OrderStatus::PENDING, OrderStatus::OPEN, OrderStatus::RUNNING])
                ->lockForUpdate()
                ->first();

            if (!$target) {
                $target = Order::create([
                    'branch_id' => $to->branch_id,
                    'table_id'  => $to->id,
                    'status'    => OrderStatus::PENDING,
                    'subtotal'  => 0,
                    'tax'       => 0,
                    'discount'  => 0,
                    'total'     => 0,
                    'order_date'=> now(),
                ]);
            }

            // Move by cloning items (keep history)
            foreach ($orders as $order) {
                foreach ($order->items as $item) {
                    $cloned = $item->replicate(['order_id','parent_item_id']);
                    $cloned->order_id = $target->id;
                    $cloned->parent_item_id = $item->id;
                    $cloned->save();

                    foreach ($item->answers as $ans) {
                        $ans->replicate(['order_item_id'])
                            ->fill(['order_item_id' => $cloned->id])
                            ->save();
                    }
                    foreach ($item->modifiers as $mod) {
                        $mod->replicate(['order_item_id'])
                            ->fill(['order_item_id' => $cloned->id])
                            ->save();
                    }
                }
                // Close source order (kept for audit)
                $order->status = OrderStatus::CLOSED;
                $order->save();
            }

            // update table statuses
            $from->status = TableStatus::OPEN;
            $from->save();
            $to->status = TableStatus::OCCUPIED;
            $to->save();

            \App\Services\Orders\RecalculateOrder::run($target);

            return response()->json(['message' => 'Table moved successfully.']);
        });
    }

   public function sendToCashier(Request $request, Order $order)
{
    if ($request->user()->branch_id !== $order->branch_id) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    $order->load('items');
    $allDone = $order->items
        ->filter(fn($i) => !in_array($i->kds_status, ['canceled','refunded']))
        ->every(fn($i) => in_array($i->kds_status, ['ready','served']));

    if (!$allDone) {
        return response()->json(['error' => 'Items are not finished in KDS'], 422);
    }

    // mark order as ready-for-cashier / or just set status if you have it
    $order->status = 'open'; // still open but payable; adapt to your flow
    $order->save();

    return response()->json(['ok' => true]);
}

public function batchSendToCashier(Request $request)
{
    $data = $request->validate([
        'order_ids' => 'required|array|min:1',
        'order_ids.*' => 'integer|exists:orders,id',
    ]);

    $ok = [];
    $failed = [];

    foreach ($data['order_ids'] as $id) {
        $order = Order::with('items')->find($id);
        $allDone = $order->items
            ->filter(fn($i) => !in_array($i->kds_status, ['canceled','refunded']))
            ->every(fn($i) => in_array($i->kds_status, ['ready','served']));
        if ($allDone) {
            $order->status = 'open';
            $order->save();
            $ok[] = $id;
        } else {
            $failed[] = $id;
        }
    }

    return response()->json(['ok' => $ok, 'failed' => $failed]);
}


    

    public function reopenOrder(Request $request, $orderId)
    {
        $order = Order::with('table')->findOrFail($orderId);
        if ($order->table->branch_id !== $request->user()->branch_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        if ($order->status !== OrderStatus::CASHIER) {
            return response()->json(['error' => 'Only cashier orders can be reopened.'], 400);
        }
        $order->status = OrderStatus::PENDING;
        $order->save();

        $order->table->update(['status' => TableStatus::OCCUPIED]);

        return response()->json(['order' => $order], 200);
    }

    private function discountedItemTotal($item, $order, $qty = null): float
    {
        $useQty = $qty ?? (int)$item->quantity;
        $itemTotal = (float)$item->price * $useQty;

        $active = $order->items()->whereNotIn('status',['refunded','cancelled'])->get();
        $orderSubtotal = $active->sum(fn($i) => (float)$i->price * (int)$i->quantity);

        if ($order->discount_type === 'percent' && $order->discount > 0) {
            return round($itemTotal * (1 - $order->discount/100), 2);
        }
        if ($order->discount_type === 'fixed' && $order->discount > 0 && $orderSubtotal > 0) {
            $proportion = $itemTotal / $orderSubtotal;
            return round($itemTotal - $order->discount * $proportion, 2);
        }
        return round($itemTotal, 2);
    }

    public function refundOrChangeItem(Request $request, $orderItemId)
{
    $item  = OrderItem::with(['order.table','product','answers','modifiers'])->findOrFail($orderItemId);
    $order = $item->order;

    if ($order->table->branch_id !== $request->user()->branch_id) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    $action = $request->input('action'); // refund, cancel, change
    $qty    = $request->has('quantity') ? (int)$request->input('quantity') : null;
    $note   = $request->input('note');
    $restoreStock = $request->boolean('restore_stock', true);
    $userId = $request->user()->id;

    $before  = $item->toArray();
    $product = $item->product;
    $branchId = $order->branch_id;

    // Adjust stock (prefer branch stock if you have it)
    $adjustStock = function($product, $qty, $direction = 1) use ($branchId) {
        $product->loadMissing('recipe.ingredients');
        if ($product->recipe) {
            foreach ($product->recipe->ingredients as $ingredient) {
                $delta = $ingredient->pivot->quantity * $qty * $direction;
                // If you track per-branch:
                DB::table('ingredient_branches')
                    ->where('ingredient_id', $ingredient->id)
                    ->where('branch_id', $branchId)
                    ->update(['stock' => DB::raw("GREATEST(0, stock + ($delta))")]);
            }
        } else {
            // fallback: product stock
            $product->increment('stock', $qty * $direction);
        }
    };

    return DB::transaction(function () use ($action, $qty, $note, $restoreStock, $userId, $item, $order, $product, $before, $adjustStock) {

        if (in_array($action, ['refund','cancel'], true)) {
            $statusWord = $action === 'refund' ? 'refunded' : 'canceled';
            $modQty = $qty ?? (int)$item->quantity;

            if ($modQty < (int)$item->quantity) {
                // PARTIAL: split a child row with refunded/canceled qty
                $remainingQty = (int)$item->quantity - $modQty;

                $item->quantity = $remainingQty;
                $item->total = $this->discountedItemTotal($item, $order, $remainingQty);
                $item->save();

                $modItem = $item->replicate(['parent_item_id']);
                $modItem->parent_item_id = $item->id;
                $modItem->quantity = $modQty;
                $modItem->total = $this->discountedItemTotal($modItem, $order, $modQty);
                $modItem->status = $statusWord;
                $modItem->kds_status = $statusWord; // HIDE on KDS
                $modItem->refunded_quantity = $modQty;
                $modItem->refunded_amount = $modItem->total;
                $modItem->change_note = $note;
                $modItem->save();

                foreach ($item->answers as $ans) {
                    $ans->replicate(['order_item_id'])->fill(['order_item_id' => $modItem->id])->save();
                }
                foreach ($item->modifiers as $mod) {
                    $mod->replicate(['order_item_id'])->fill(['order_item_id' => $modItem->id])->save();
                }

                if ($restoreStock) $adjustStock($product, $modQty, +1);

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

                // notify KDS to remove split row quickly
                event(new \App\Events\KDSItemStatusUpdated($modItem));

            } else {
                // FULL
                $item->status = $statusWord;
                $item->kds_status = $statusWord; // HIDE on KDS
                $item->refunded_quantity = $modQty;
                $item->total = $this->discountedItemTotal($item, $order, $modQty);
                $item->refunded_amount = $item->total;
                $item->change_note = $note;
                $item->save();

                if ($restoreStock) $adjustStock($product, $modQty, +1);

                \App\Models\OrderItemHistory::create([
                    'order_item_id' => $item->id,
                    'action' => $action,
                    'snapshot_before' => json_encode($before),
                    'snapshot_after' => json_encode($item->toArray()),
                    'note' => $note,
                    'user_id' => $userId,
                ]);

                event(new \App\Events\KDSItemStatusUpdated($item));
            }

        } elseif ($action === 'change') {
            $oldQty = (int)$item->quantity;
            $newQty = $qty ?? $oldQty;

            $item->quantity = $newQty;
            $item->total = $this->discountedItemTotal($item, $order, $newQty);
            $item->status = 'changed';
            $item->change_note = $note;
            $item->save();

            if ($restoreStock && $newQty < $oldQty) {
                $diff = $oldQty - $newQty;
                $adjustStock($product, $diff, +1);
            }

            \App\Models\OrderItemHistory::create([
                'order_item_id' => $item->id,
                'action' => $action,
                'snapshot_before' => json_encode($before),
                'snapshot_after' => json_encode($item->toArray()),
                'note' => $note,
                'user_id' => $userId,
            ]);

            // tell the KDS that quantities changed
            event(new \App\Events\KDSItemStatusUpdated($item));
        }

        // Recompute and return fresh totals
        $order = \App\Services\Orders\RecalculateOrder::run($order);

        return response()->json([
            'items' => $order->items()->with('product')->get(),
            'order_total' => $order->total,
            'order_subtotal' => $order->subtotal,
            'order_tax' => $order->tax,
        ]);
    });
}


    public function itemHistory(Request $request, $orderItemId)
    {
        $histories = \App\Models\OrderItemHistory::where('order_item_id', $orderItemId)
            ->with('user:id,name')
            ->orderBy('created_at','desc')
            ->get();

        return response()->json(['history' => $histories]);
    }

    

}
