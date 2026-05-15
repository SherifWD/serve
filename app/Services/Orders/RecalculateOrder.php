<?php
namespace App\Services\Orders;

use App\Models\FiscalProfile;
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
        $profile = FiscalProfile::effectiveForBranch((int) $order->branch_id);
        $rate = max((float) $profile->vat_rate, 0.0);

        if ($profile->price_includes_vat && $rate > 0) {
            $net = round($taxable / (1 + $rate), 2);
            $tax = round($taxable - $net, 2);
            $total = round($taxable, 2);
        } else {
            $net = round($taxable, 2);
            $tax = round($net * $rate, 2);
            $total = round($net + $tax, 2);
        }

        $order->subtotal = $net;
        $order->tax = $tax;
        $order->total = $total;
        $order->save();

        return $order->fresh(['items.product','table']);
    }
}
