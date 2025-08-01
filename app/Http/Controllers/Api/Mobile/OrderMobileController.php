<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\CategoryAnswer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Table;
use Barryvdh\DomPDF\PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderMobileController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::query();

        if ($request->has('table_id')) {
            $query->where('table_id', $request->input('table_id'));
        }

        $orders = $query->with('items.product')->get();

        return response()->json(['data' => $orders]);
    }

    public function show($id)
{
    $table = Table::with(['orders' => function($q) {
            $q->whereIn('status', ['pending', 'open']);
        }, 'orders.items.product'])
        ->findOrFail($id);

    // You might want to get only the first open/pending order
    $order = optional($table->orders)->first();

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
        'discount' => 'nullable|numeric|min:0',
        'discount_type' => 'nullable|in:fixed,percent',
        'coupon_code' => 'nullable|string|max:50'
    ]);
    DB::beginTransaction();
    try {
        $table = Table::findOrFail($data['table_id']);
        $openOrder = Order::where('table_id', $table->id)
            ->whereIn('status', ['pending', 'open'])
            ->first();

        $order = $openOrder ?: Order::create([
            'branch_id' => $table->branch_id ?? 1,
            'table_id'  => $table->id,
            'status'    => 'pending',
            'total'     => 0,
            'order_date' => now(),
        ]);
        $orderTotal = $order->total;

        foreach ($data['items'] as $item) {
            $product = Product::findOrFail($item['product_id']);
            $itemTotal = $product->price * $item['quantity'];
            $itemDiscount = $item['discount'] ?? 0;
            $itemDiscountType = $item['discount_type'] ?? 'fixed';

            if ($itemDiscount > 0) {
                $itemTotal -= ($itemDiscountType === 'percent')
                    ? ($itemTotal * $itemDiscount / 100)
                    : $itemDiscount;
            }

            $orderItem = OrderItem::create([
                'order_id'   => $order->id,
                'product_id' => $product->id,
                'quantity'   => $item['quantity'],
                'price'      => $product->price,
                'total'      => $itemTotal,
                'discount'   => $itemDiscount,
                'discount_type' => $itemDiscountType
            ]);
            $orderTotal += $itemTotal;

            // Handle answers
            if (!empty($item['answers'])) {
                foreach ($item['answers'] as $answer) {
                    CategoryAnswer::create([
                        'order_item_id' => $orderItem->id,
                        'choice_id'     => $answer['choice_id'],
                        'image'         => $answer['image'] ?? null,
                    ]);
                }
            }

            // Handle modifiers (NEW)
            if (!empty($item['modifiers']) && is_array($item['modifiers'])) {
                foreach ($item['modifiers'] as $mod) {
                    if (!empty($mod['modifier_id']) || !empty($mod['raw_modifier'])) {
                        \App\Models\OrderItemModifier::create([
                            'order_item_id' => $orderItem->id,
                            'modifier_id'   => $mod['modifier_id'] ?? null,
                            'raw_modifier'  => $mod['raw_modifier'] ?? null,
                        ]);
                    }
                }
            }
        }

        // Apply order-level discount/coupon
        $orderDiscount = $data['discount'] ?? 0;
        $orderDiscountType = $data['discount_type'] ?? 'fixed';

        if ($orderDiscount > 0) {
            $orderTotal -= ($orderDiscountType === 'percent')
                ? ($orderTotal * $orderDiscount / 100)
                : $orderDiscount;
        }
        $order->discount = $orderDiscount;
        $order->discount_type = $orderDiscountType;
        $order->coupon_code = $data['coupon_code'] ?? null;
        $order->total = max($orderTotal, 0);
        $order->save();

        $table->status = 'occupied';
        $table->save();

        DB::commit();
        $order->load(['items.product', 'items.answers.choice', 'items.modifiers.modifier']); // <-- eager load modifiers and their names

        return response()->json(['order' => $order], $openOrder ? 200 : 201);

    } catch (\Throwable $e) {
        DB::rollBack();
        return response()->json([
            'error' => 'Order creation failed.',
            'details' => $e->getMessage()
        ], 500);
    }
}


public function pay(Request $request, $id)
{
    $data = $request->validate([
        'amount' => 'required|numeric|min:0',
        'payment_method' => 'required|string|max:30',
        'email_receipt' => 'nullable|email'
    ]);
    $order = Order::findOrFail($id);

    if ($data['amount'] < $order->total) {
        $order->payment_status = 'partial';
    } else {
        $order->payment_status = 'paid';
        $order->paid_at = now();
    }
    $order->payment_method = $data['payment_method'];
    $order->save();

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

}
