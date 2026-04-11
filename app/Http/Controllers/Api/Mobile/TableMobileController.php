<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Enums\OrderStatus;
use App\Enums\TableStatus;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Table;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TableMobileController extends Controller
{
    private const ACTIVE_ORDER_STATUSES = ['pending', 'open', 'running', 'cashier'];
    private const NON_ACTIVE_ITEM_STATUSES = ['canceled', 'cancelled', 'refunded'];

    private function nonNegativeStockExpression(float|int $delta): string
    {
        $delta = (float) $delta;

        if (DB::connection()->getDriverName() === 'sqlite') {
            return "CASE WHEN stock + ($delta) < 0 THEN 0 ELSE stock + ($delta) END";
        }

        return "GREATEST(stock + ($delta), 0)";
    }

    private function ensureBranchAccessible(Request $request, int $branchId): ?JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if ($user->branch_id) {
            return (int) $user->branch_id === $branchId
                ? null
                : response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($user->restaurant_id) {
            $allowed = Branch::query()
                ->whereKey($branchId)
                ->where('restaurant_id', $user->restaurant_id)
                ->exists();

            return $allowed
                ? null
                : response()->json(['error' => 'Unauthorized'], 403);
        }

        return null;
    }

    private function tableQueryForUser(Request $request)
    {
        $query = Table::query();
        $user = $request->user();

        if ($user?->branch_id) {
            $query->where('branch_id', $user->branch_id);
        } elseif ($user?->restaurant_id) {
            $query->whereHas('branch', fn ($branchQuery) => $branchQuery->where('restaurant_id', $user->restaurant_id));
        }

        return $query;
    }

    private function activeOrders($query)
    {
        return $query->whereIn('status', self::ACTIVE_ORDER_STATUSES)
            ->where(function ($paymentQuery) {
                $paymentQuery->whereNull('payment_status')
                    ->orWhere('payment_status', '!=', 'paid');
            });
    }

    private function tableRelations(): array
    {
        return [
            'orders' => fn($q) => $this->activeOrders($q)
                ->select('id','branch_id','table_id','customer_id','order_type','status','payment_status','subtotal','tax','discount','discount_type','total','coupon_code','order_date','kds_sent_at')
                ->latest('id'),
            'orders.items' => fn($q) => $q->select(
                'id','order_id','product_id','quantity','price','total',
                'status','kds_status','item_note','change_note'
            ),
            'orders.payments:id,order_id,method,amount,item_ids,scope',
            'orders.customer:id,name,phone',
            'orders.items.product:id,name,image',
            'orders.items.answers.choice.question',
            'orders.items.modifiers.modifier',
        ];
    }

    private function hydrateTableState(Table $table): void
    {
        $order = $table->orders->first();
        $serviceStatus = 'available';

        if ($order) {
            $order->items->each(function ($item) use ($order) {
                $item->setRelation('order', $order);
                $item->append(['item_note', 'change_note', 'paid_amount', 'payment_status']);
            });

            $activeItems = $order->items->reject(function ($item) {
                $status = $item->kds_status ?? $item->status;
                return in_array($status, self::NON_ACTIVE_ITEM_STATUSES, true);
            });

            $kdsStatuses = $activeItems
                ->map(fn ($item) => $item->kds_status ?? $item->status)
                ->filter()
                ->values();

            $serviceStatus = match (true) {
                $order->status === OrderStatus::CASHIER => 'cashier',
                $kdsStatuses->contains('returned') => 'returned',
                $kdsStatuses->contains(fn ($status) => in_array($status, ['queued', 'preparing'], true)) => 'kitchen',
                $kdsStatuses->contains('ready') => 'ready',
                $kdsStatuses->contains('served') => 'served',
                default => 'busy',
            };
        }

        $table->setAttribute('service_status', $serviceStatus);
        $table->setAttribute('active_order_status', $order?->status);
        $table->setAttribute('active_payment_status', $order?->payment_status);
    }

    private function tableMatchesStatus(Table $table, string $filter): bool
    {
        $status = $table->getAttribute('service_status') ?? 'available';

        return match ($filter) {
            'all' => true,
            'available', 'open' => $status === 'available',
            'busy', 'occupied' => $status !== 'available',
            'kds', 'kitchen' => $status === 'kitchen',
            'cashier', 'sent_to_cashier' => $status === 'cashier',
            'ready' => $status === 'ready',
            'served' => $status === 'served',
            'returned' => $status === 'returned',
            default => true,
        };
    }

    public function index(Request $request)
    {
        $tables = $this->tableQueryForUser($request)
            ->with($this->tableRelations())
            ->get();

        $tables->each(fn ($table) => $this->hydrateTableState($table));

        $filter = strtolower((string) $request->input('status', 'all'));
        if ($filter !== 'all') {
            $tables = $tables
                ->filter(fn ($table) => $this->tableMatchesStatus($table, $filter))
                ->values();
        }

        return response()->json(['data' => $tables]);
    }

   public function show(Request $request, $id)
{
    $table = $this->tableQueryForUser($request)
        ->with($this->tableRelations())
        ->findOrFail($id);

    $this->hydrateTableState($table);

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

            $sourceCustomerId = $orders->pluck('customer_id')->filter()->first();
            $sourceKdsSentAt = $orders->pluck('kds_sent_at')->filter()->sort()->first();

            // Ensure a target open order
            $target = Order::where('table_id', $to->id)
                ->whereIn('status', [OrderStatus::PENDING, OrderStatus::OPEN, OrderStatus::RUNNING])
                ->lockForUpdate()
                ->first();

            if (!$target) {
                $target = Order::create([
                    'branch_id' => $to->branch_id,
                    'table_id'  => $to->id,
                    'customer_id' => $sourceCustomerId,
                    'order_type'=> 'dine-in',
                    'status'    => OrderStatus::PENDING,
                    'subtotal'  => 0,
                    'tax'       => 0,
                    'discount'  => 0,
                    'total'     => 0,
                    'order_date'=> now(),
                ]);
            } elseif (!$target->customer_id && $sourceCustomerId) {
                $target->customer_id = $sourceCustomerId;
                $target->save();
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

            if (!$target->kds_sent_at && $sourceKdsSentAt) {
                $target->kds_sent_at = $sourceKdsSentAt;
                $target->save();
            }

            \App\Services\Orders\RecalculateOrder::run($target);

            return response()->json(['message' => 'Table moved successfully.']);
        });
    }

   public function sendToCashier(Request $request, Order $order)
{
    if ($authResponse = $this->ensureBranchAccessible($request, (int) $order->branch_id)) {
        return $authResponse;
    }

    $order->load('items');
    $activeItems = $order->items->filter(function ($i) {
        $status = $i->kds_status ?? $i->status;
        return !in_array($status, self::NON_ACTIVE_ITEM_STATUSES, true);
    });
    $allDone = $activeItems->isNotEmpty() && $activeItems->every(function ($i) {
        $status = $i->kds_status ?? $i->status;
        return in_array($status, ['ready', 'served'], true);
    });

    if (!$allDone) {
        return response()->json(['error' => 'Items are not finished in KDS'], 422);
    }

    $order->status = 'cashier'; // <— important
    $order->save();
    if ($order->table && $order->order_type === 'dine-in') {
        $order->table->update(['status' => TableStatus::CASHIER]);
    }

    // (optional) broadcast to waiter & cashier UIs
    // event(new \App\Events\OrderReadyForCashier($order));

    return response()->json(['ok' => true]);
}

public function batchSendToCashier(Request $request)
{
    $data = $request->validate([
        'order_ids' => 'required|array|min:1',
        'order_ids.*' => 'integer|exists:orders,id',
    ]);

    $ok = []; $failed = [];
    foreach ($data['order_ids'] as $id) {
        $order = Order::with('items')->find($id);
        if (!$order || $this->ensureBranchAccessible($request, (int) $order->branch_id)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $activeItems = $order->items->filter(function ($i) {
            $status = $i->kds_status ?? $i->status;
            return !in_array($status, self::NON_ACTIVE_ITEM_STATUSES, true);
        });
        $allDone = $activeItems->isNotEmpty() && $activeItems->every(function ($i) {
            $status = $i->kds_status ?? $i->status;
            return in_array($status, ['ready', 'served'], true);
        });

        if ($allDone) {
            $order->status = 'cashier'; // <— important
            $order->save();
            if ($order->table && $order->order_type === 'dine-in') {
                $order->table->update(['status' => TableStatus::CASHIER]);
            }
            $ok[] = $id;
            // event(new \App\Events\OrderReadyForCashier($order));
        } else {
            $failed[] = $id;
        }
    }

    return response()->json(['ok' => $ok, 'failed' => $failed]);
}



    

    public function reopenOrder(Request $request, $orderId)
    {
        $order = Order::with('table')->findOrFail($orderId);
        if ($authResponse = $this->ensureBranchAccessible($request, (int) $order->table->branch_id)) {
            return $authResponse;
        }
        if (!in_array($order->status, [OrderStatus::CASHIER, OrderStatus::PAID], true)) {
            return response()->json(['error' => 'Only cashier or paid orders can be reopened.'], 400);
        }
        $order->status = OrderStatus::PENDING;
        if ((float) $order->payments()->sum('amount') < (float) $order->total) {
            $order->payment_status = 'partial';
        }
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

    if ($authResponse = $this->ensureBranchAccessible($request, (int) $order->table->branch_id)) {
        return $authResponse;
    }

    $data = $request->validate([
        'action' => 'required|in:refund,cancel,change,return',
        'quantity' => 'nullable|integer|min:1',
        'note' => 'nullable|string|max:255',
        'restore_stock' => 'nullable|boolean',
    ]);

    $action = $data['action'];
    $qty = array_key_exists('quantity', $data) ? (int) $data['quantity'] : null;
    $note = $data['note'] ?? null;
    $restoreStock = $request->boolean('restore_stock', true);
    $userId = $request->user()->id;

    if ($qty !== null && in_array($action, ['refund', 'cancel'], true) && $qty > (int) $item->quantity) {
        return response()->json([
            'error' => 'Quantity cannot exceed the original item quantity.',
        ], 422);
    }

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
                    ->update(['stock' => DB::raw($this->nonNegativeStockExpression($delta))]);
            }
        } else {
            // fallback: product stock
            $product->increment('stock', $qty * $direction);
        }
    };

    return DB::transaction(function () use ($action, $qty, $note, $restoreStock, $userId, $item, $order, $product, $before, $adjustStock) {

        if ($action === 'return') {
            $item->status = 'returned';
            $item->kds_status = 'returned';
            $item->kds_sent_at = now();
            $item->change_note = $note;
            $item->save();

            if (!$order->kds_sent_at) {
                $order->kds_sent_at = now();
            }
            $order->status = OrderStatus::PENDING;
            $order->save();
            $order->table?->update(['status' => TableStatus::OCCUPIED]);

            \App\Models\OrderItemHistory::create([
                'order_item_id' => $item->id,
                'action' => $action,
                'snapshot_before' => json_encode($before),
                'snapshot_after' => json_encode($item->toArray()),
                'note' => $note,
                'user_id' => $userId,
            ]);

            // event(new \App\Events\KDSItemStatusUpdated($item));
        } elseif (in_array($action, ['refund','cancel'], true)) {
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
                // event(new \App\Events\KDSItemStatusUpdated($modItem));

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

                // event(new \App\Events\KDSItemStatusUpdated($item));
            }

        } elseif ($action === 'change') {
            $oldQty = (int)$item->quantity;
            $newQty = $qty ?? $oldQty;

            $item->quantity = $newQty;
            $item->total = $this->discountedItemTotal($item, $order, $newQty);
            $item->status = 'changed';
            $item->change_note = $note;
            $item->save();

            if ($restoreStock && $newQty !== $oldQty) {
                $diff = abs($oldQty - $newQty);
                $adjustStock($product, $diff, $newQty < $oldQty ? +1 : -1);
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
            // event(new \App\Events\KDSItemStatusUpdated($item));
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
        $item = OrderItem::with('order.table')->findOrFail($orderItemId);

        if ($authResponse = $this->ensureBranchAccessible($request, (int) $item->order->table->branch_id)) {
            return $authResponse;
        }

        $histories = \App\Models\OrderItemHistory::where('order_item_id', $orderItemId)
            ->with('user:id,name')
            ->orderBy('created_at','desc')
            ->get();

        return response()->json(['history' => $histories]);
    }

    

}
