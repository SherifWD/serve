<?php
namespace App\Services\Orders;

use App\Models\Order;

class RecalculateOrder {
    public static function run(Order $order): Order {
        // Only active items count in totals
        $items = $order->items()->whereNotIn('status', ['refunded','cancelled'])->get();

        $subtotal = $items->sum(fn($i) => (float)$i->price * (int)$i->quantity);

        // Order discount
        $discount = 0.0;
        if ($order->discount_type === 'percent' && $order->discount > 0) {
            $discount = $subtotal * ((float)$order->discount / 100.0);
        } elseif ($order->discount_type === 'fixed' && $order->discount > 0) {
            $discount = min((float)$order->discount, $subtotal);
        }

        $taxable = max($subtotal - $discount, 0.0);

        // TODO: Replace with your actual tax engine (per-branch, per-item VAT, etc.)
        // For now, we use existing $order->tax as-is.
        $tax = (float)($order->tax ?? 0.0);

        $order->subtotal = round($subtotal, 2);
        // If computing tax dynamically: $order->tax = round($taxable * $rate, 2);
        $order->total    = round($taxable + $tax, 2);
        $order->save();

        return $order->fresh(['items.product','table']);
    }
}
