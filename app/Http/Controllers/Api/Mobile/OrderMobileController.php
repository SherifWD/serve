<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Events\OrderSentToKDS;
use App\Http\Controllers\Controller;
use App\Models\CategoryAnswer;
use App\Models\Customer;
use App\Models\LoyaltyTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Table;
use Barryvdh\DomPDF\PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class OrderMobileController extends Controller
{
    private function nonNegativeStockExpression(int $delta): string
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return "CASE WHEN stock + ($delta) < 0 THEN 0 ELSE stock + ($delta) END";
        }

        return "GREATEST(stock + ($delta), 0)";
    }

    public function index(Request $request)
    {
        $query = Order::query();

        if ($request->has('table_id')) {
            $query->where('table_id', $request->input('table_id'));
        }

        if ($request->filled('status')) {
            $statuses = collect(explode(',', (string) $request->input('status')))
                ->map(fn ($value) => trim($value))
                ->filter()
                ->values();

            if ($statuses->isNotEmpty()) {
                $query->whereIn('status', $statuses);
            }
        }

        $orders = $query->with([
                'branch.restaurant',
                'table',
                'customer',
                'items.product',
                'items.modifiers.modifier',
                'payments',
            ])
            ->latest('id')
            ->get();

$orders->each(function($order){
    $order->items->each(function($item){
        $item->append(['item_note','change_note']);
    });
});


        return response()->json(['data' => $orders]);
    }
public function sendToCashier(Request $request, Order $order)
{
    if ($request->user()->branch_id !== $order->branch_id) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    $order->load('items');
    $allDone = $order->items
    ->filter(function ($i) {
        $status = $i->kds_status ?? $i->status; // fallback if NULL
        return !in_array($status, ['canceled', 'refunded', 'cancelled']);
    })
    ->every(function ($i) {
        $status = $i->kds_status ?? $i->status;
        return in_array($status, ['ready', 'served']);
    });

    if (!$allDone) {
        return response()->json(['error' => 'Items are not finished in KDS'], 422);
    }

    $order->status = 'cashier'; // <— important
    $order->save();

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
        $allDone = $order->items
    ->filter(function ($i) {
        $status = $i->kds_status ?? $i->status; // fallback if NULL
        return !in_array($status, ['canceled', 'refunded', 'cancelled']);
    })
    ->every(function ($i) {
        $status = $i->kds_status ?? $i->status;
        return in_array($status, ['ready', 'served']);
    });

        if ($allDone) {
            $order->status = 'cashier'; // <— important
            $order->save();
            $ok[] = $id;
            // event(new \App\Events\OrderReadyForCashier($order));
        } else {
            $failed[] = $id;
        }
    }

    return response()->json(['ok' => $ok, 'failed' => $failed]);
}
    public function show($id)
{
    $table = Table::with(['orders' => function($q) {
            $q->whereIn('status', ['pending', 'open']);
        }, 'orders.items.product'])
        ->findOrFail($id);

    // You might want to get only the first open/pending order
    $order = optional($table->orders)->first();
if ($order) {
    $order->items->each(function($item){
        $item->append(['item_note','change_note']);
    });
}

    return response()->json([
        'data' => [
            'id' => $table->id,
            'name' => $table->name,
            'order' => $order // Will include items and product data
        ]
    ]);
}


    public function store(Request $request)
{
    $data = $request->validate([
        'table_id' => 'required|integer|exists:tables,id',
        'order_type' => 'nullable|in:dine-in,takeaway,delivery',
        'customer_id' => 'nullable|integer|exists:customers,id',
        'customer_name' => 'nullable|string|max:255',
        'customer_phone' => 'nullable|string|max:30',
        'customer_email' => 'nullable|email|max:255',
        'items' => 'required|array|min:1',
        'items.*.product_id' => 'required|integer|exists:products,id',
        'items.*.quantity' => 'required|integer|min:1',
        'items.*.answers' => 'nullable|array',
        'items.*.answers.*.choice_id' => 'required|integer|exists:category_choices,id',
        'items.*.discount' => 'nullable|numeric|min:0',
        'items.*.discount_type' => 'nullable|in:fixed,percent',
        'items.*.modifiers' => 'nullable|array',
        'items.*.modifiers.*.modifier_id' => 'nullable|exists:modifiers,id',
        'items.*.modifiers.*.raw_modifier' => 'nullable|string|max:255',
        'items.*.note' => 'nullable|string|max:255', // NEW
        'discount' => 'nullable|numeric|min:0',
        'discount_type' => 'nullable|in:fixed,percent',
        'coupon_code' => 'nullable|string|max:50'
    ]);

    DB::beginTransaction();
    try {
        $table = Table::findOrFail($data['table_id']);
        $customer = $this->resolveCustomer($data);

        // Branch guard (optional)
        if ($request->user() && $table->branch_id !== $request->user()->branch_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $openOrder = Order::where('table_id', $table->id)
            ->whereIn('status', ['pending', 'open'])
            ->lockForUpdate()
            ->first();

        $order = $openOrder ?: Order::create([
            'branch_id' => $table->branch_id ?? 1,
            'table_id'  => $table->id,
            'customer_id' => $customer?->id,
            'order_type'=> $data['order_type'] ?? 'dine-in',
            'status'    => 'pending',
            'subtotal'  => 0,
            'tax'       => 0,
            'discount'  => 0,
            'total'     => 0,
            'order_date' => now(),
        ]);

        if ($openOrder && !$order->customer_id && $customer) {
            $order->customer_id = $customer->id;
            $order->save();
        }

        $orderTotal = $order->total;
        $branchId = $table->branch_id;

        // Helper: sum modifier price for modifier_ids
        $sumModifierPrice = function(array $mods): float {
            if (empty($mods)) return 0.0;
            $ids = array_values(array_filter(array_map(fn($m) => $m['modifier_id'] ?? null, $mods)));
            if (empty($ids)) return 0.0;
            $rows = \App\Models\Modifier::whereIn('id', $ids)->get(['id','price']);
            $byId = $rows->keyBy('id');
            $sum = 0.0;
            foreach ($ids as $id) {
                $p = $byId[$id]->price ?? 0;
                $sum += (float)$p;
            }
            return $sum;
        };

        // Helper: adjust stock (recipes or product), direction -1 on create
        $adjustStock = function(\App\Models\Product $product, int $qty, int $direction = -1) use ($branchId) {
            $product->loadMissing('recipe.ingredients');

            if ($product->recipe && $product->recipe->ingredients->count()) {
                foreach ($product->recipe->ingredients as $ingredient) {
                    $pivotQty = (float)$ingredient->pivot->quantity;
                    $delta = $pivotQty * $qty * $direction;

                    // Prefer ingredient_branches if exists; else fallback to ingredients.stock
                    $ib = \DB::table('ingredient_branches')
                        ->where('ingredient_id', $ingredient->id)
                        ->where('branch_id', $branchId)
                        ->first();

                    if ($ib) {
                        // update stock safely
                        \DB::table('ingredient_branches')
                            ->where('id', $ib->id)
                            ->update(['stock' => \DB::raw("GREATEST(stock + ($delta), 0)")]); // prevent negative
                    } else {
                        // fallback
                        $ingredient->update(['stock' => max(0, (float)$ingredient->stock + $delta)]);
                    }
                }
            } else {
                // simple product stock (if you track it on products)
                if (Schema::hasColumn('products','stock')) {
                    $delta = $qty * $direction;
                    $product->update(['stock' => \DB::raw($this->nonNegativeStockExpression($delta))]);
                }
            }
        };

        // (Optional) Validate required questions if your data has 'required'
        $questionsByCategory = []; // if you can provide, otherwise skip checking

        foreach ($data['items'] as $item) {
            $product = Product::findOrFail($item['product_id']);

            // --- compute base + modifiers ---
            $baseUnit = (float)$product->price;
            $mods = $item['modifiers'] ?? [];
            $modsUnit = $sumModifierPrice($mods); // price per 1 unit of product
            $unitPrice = $baseUnit + $modsUnit;

            $qty = (int)$item['quantity'];
            $itemTotal = $unitPrice * $qty;

            // item-level discount
            $itemDiscount = (float)($item['discount'] ?? 0);
            $itemDiscountType = $item['discount_type'] ?? 'fixed';
            if ($itemDiscount > 0) {
                $itemTotal -= ($itemDiscountType === 'percent')
                    ? ($itemTotal * $itemDiscount / 100.0)
                    : $itemDiscount;
            }
            $itemTotal = max(0, round($itemTotal, 2));

            $orderItem = OrderItem::create([
                'order_id'   => $order->id,
                'product_id' => $product->id,
                'quantity'   => $qty,
                'price'      => $unitPrice, // store unit price INCLUDING modifier price
                'total'      => $itemTotal,
                'discount'   => $itemDiscount,
                'discount_type' => $itemDiscountType,
                'change_note' => $item['note'] ?? null, // use as item note on create
                'item_note' => $item['note'] ?? null, // use as item note on create
            ]);
            $orderTotal += $itemTotal;

            // answers
            if (!empty($item['answers'])) {
                foreach ($item['answers'] as $answer) {
                    CategoryAnswer::create([
                        'order_item_id' => $orderItem->id,
                        'choice_id'     => $answer['choice_id'],
                        'image'         => $answer['image'] ?? null,
                    ]);
                }
            }

            // modifiers (link rows)
            if (!empty($mods) && is_array($mods)) {
                foreach ($mods as $mod) {
                    if (!empty($mod['modifier_id']) || !empty($mod['raw_modifier'])) {
                        \App\Models\OrderItemModifier::create([
                            'order_item_id' => $orderItem->id,
                            'modifier_id'   => $mod['modifier_id'] ?? null,
                            'raw_modifier'  => $mod['raw_modifier'] ?? null,
                        ]);
                    }
                }
            }

            // stock deduction
            $adjustStock($product, $qty, -1);
        }

        // order-level discount
        $orderDiscount = (float)($data['discount'] ?? 0);
        $orderDiscountType = $data['discount_type'] ?? 'fixed';
        if ($orderDiscount > 0) {
            $orderTotal -= ($orderDiscountType === 'percent')
                ? ($orderTotal * $orderDiscount / 100.0)
                : $orderDiscount;
        }

        $order->discount = $orderDiscount;
        $order->discount_type = $orderDiscountType;
        $order->coupon_code = $data['coupon_code'] ?? null;
        $order->total = max($orderTotal, 0);
        $order->save();

        $table->status = \App\Enums\TableStatus::OCCUPIED;
        $table->save();

        // recompute unified subtotal/tax/total (keeps fields consistent)
        $order = \App\Services\Orders\RecalculateOrder::run($order);

        $paidAmount = (float) $order->payments()->sum('amount');
        if ($paidAmount > 0) {
            $order->payment_status = $paidAmount >= (float) $order->total ? 'paid' : 'partial';
            if ($order->payment_status !== 'paid' && $order->status === 'paid') {
                $order->status = 'pending';
            }
            $order->save();
        }

        DB::commit();

        $order->load(['branch.restaurant', 'customer', 'items.product', 'items.answers.choice.question', 'items.modifiers.modifier', 'payments']); // eager
        return response()->json(['order' => $order], $openOrder ? 200 : 201);

    } catch (\Throwable $e) {
        DB::rollBack();
        return response()->json([
            'error' => 'Order creation failed.',
            'details' => $e->getMessage()
        ], 500);
    }
}

public function sendToKDS(Request $request, Order $order)
{
    // Authz: same branch
    if ($request->user()->branch_id !== $order->branch_id) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    // Mark only items that are still pending
    $updated = $order->items()
        ->whereIn('kds_status', ['pending']) // not previously sent
        ->update([
            'kds_status' => 'queued',
            'kds_sent_at' => now(),
        ]);

    if ($updated > 0 && !$order->kds_sent_at) {
        $order->kds_sent_at = now();
        $order->save();
    }

    // Broadcast to branch — KDS should refresh/append ticket
    // event(new OrderSentToKDS($order));

    return response()->json(['ok' => true, 'order_id' => $order->id]);
}

public function pay(Request $request, $id)
{
    $data = $request->validate([
        'amount' => 'nullable|numeric|min:0',
        'payment_method' => 'nullable|string|max:30',
        'email_receipt' => 'nullable|email',
        'payments' => 'nullable|array|min:1',
        'payments.*.method' => 'required_with:payments|in:cash,card,wallet',
        'payments.*.amount' => 'required_with:payments|numeric|min:0.01',
    ]);

    if (empty($data['payments']) && (!isset($data['amount']) || empty($data['payment_method']))) {
        return response()->json(['error' => 'Provide either a single payment or a payments split array.'], 422);
    }

    $order = Order::with(['table', 'customer', 'payments'])->findOrFail($id);

    DB::transaction(function () use ($data, $order) {
        if (!empty($data['payments'])) {
            foreach ($data['payments'] as $payment) {
                Payment::create([
                    'order_id' => $order->id,
                    'method' => $payment['method'],
                    'amount' => $payment['amount'],
                ]);
            }
            $order->payment_method = count($data['payments']) > 1 ? 'mixed' : $data['payments'][0]['method'];
        } else {
            Payment::create([
                'order_id' => $order->id,
                'method' => $data['payment_method'],
                'amount' => $data['amount'],
            ]);
            $order->payment_method = $data['payment_method'];
        }

        $paidAmount = (float) $order->payments()->sum('amount');
        if ($paidAmount >= (float) $order->total) {
            $order->payment_status = 'paid';
            $order->status = 'paid';
            $order->paid_at = now();

            if ($order->table && $order->order_type === 'dine-in') {
                $order->table->update(['status' => \App\Enums\TableStatus::OPEN]);
            }

            if ($order->customer && !$order->customer->loyaltyTransactions()->where('order_id', $order->id)->where('type', 'earn')->exists()) {
                $earnedPoints = max(1, (int) floor(((float) $order->total) / 10));

                LoyaltyTransaction::create([
                    'customer_id' => $order->customer->id,
                    'order_id' => $order->id,
                    'points' => $earnedPoints,
                    'type' => 'earn',
                ]);

                $order->customer->increment('loyalty_points', $earnedPoints);
            }
        } else {
            $order->payment_status = $paidAmount > 0 ? 'partial' : 'unpaid';
            $order->status = 'cashier';
        }

        $order->save();
    });

    $order->refresh()->load(['branch.restaurant', 'customer', 'items.product', 'payments']);

    // Optionally send email receipt
    if (!empty($data['email_receipt'])) {
        // see below for mailing
    }
    return response()->json(['order' => $order]);
}

public function receipt($id)
{
    $order = Order::with('items.product', 'table')->findOrFail($id);
    $pdf = \PDF::loadView('receipts.order', compact('order'));
    return $pdf->download("receipt_order_{$order->id}.pdf");
}

private function resolveCustomer(array $data): ?Customer
{
    if (!empty($data['customer_id'])) {
        return Customer::find($data['customer_id']);
    }

    if (empty($data['customer_phone']) && empty($data['customer_email'])) {
        return null;
    }

    $customer = Customer::query()
        ->when(
            !empty($data['customer_phone']),
            fn ($query) => $query->where('phone', $data['customer_phone'])
        )
        ->when(
            empty($data['customer_phone']) && !empty($data['customer_email']),
            fn ($query) => $query->where('email', $data['customer_email'])
        )
        ->first();

    if (!$customer) {
        return Customer::create([
            'name' => $data['customer_name'] ?? 'Walk-in Customer',
            'phone' => $data['customer_phone'] ?? null,
            'email' => $data['customer_email'] ?? null,
        ]);
    }

    $customer->fill(array_filter([
        'name' => $data['customer_name'] ?? null,
        'phone' => $data['customer_phone'] ?? null,
        'email' => $data['customer_email'] ?? null,
    ], fn ($value) => filled($value)))->save();

    return $customer;
}

}
