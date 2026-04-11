<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory; protected $guarded =[];
    public function order() {
    return $this->belongsTo(Order::class);
}

public function product() {
    return $this->belongsTo(Product::class);
}

public function modifier()
{
    return $this->hasMany(OrderItemModifier::class);
}
public function modifiers()
{
    return $this->hasMany(\App\Models\OrderItemModifier::class);
}

    public function answers() {
        return $this->hasMany(\App\Models\CategoryAnswer::class, 'order_item_id');
    }

    

    public function parent() { return $this->belongsTo(self::class, 'parent_item_id'); }
    public function children() { return $this->hasMany(self::class, 'parent_item_id'); }
public function getItemNoteAttribute($value)
{
    // fallback to legacy 'note' if needed
    return $value ?? $this->attributes['note'] ?? null;
}

public function getChangeNoteAttribute($value)
{
    return $value; // or any transform you want
}

public function getPaidAmountAttribute(): float
{
    if (in_array($this->status, ['refunded', 'canceled', 'cancelled'], true)) {
        return 0.0;
    }

    $order = $this->relationLoaded('order')
        ? $this->order
        : $this->order()->with('payments')->first();

    if (!$order) {
        return 0.0;
    }

    $payments = $order->relationLoaded('payments')
        ? $order->payments
        : $order->payments()->get();

    $orderPaid = (float) $payments->sum('amount');
    $orderTotal = (float) ($order->total ?? 0);
    $itemTotal = (float) ($this->total ?? 0);

    if ($orderTotal > 0 && $orderPaid >= $orderTotal) {
        return round($itemTotal, 2);
    }

    $paid = 0.0;
    foreach ($payments as $payment) {
        $itemIds = $this->paymentItemIds($payment->item_ids ?? null);
        if (!in_array((int) $this->id, $itemIds, true)) {
            continue;
        }

        if (count($itemIds) <= 1) {
            $paid += (float) $payment->amount;
            continue;
        }

        $selectedTotal = (float) self::query()
            ->whereIn('id', $itemIds)
            ->whereNotIn('status', ['refunded', 'canceled', 'cancelled'])
            ->sum('total');

        $paid += $selectedTotal > 0
            ? ((float) $payment->amount * ($itemTotal / $selectedTotal))
            : 0.0;
    }

    return round(min($paid, $itemTotal), 2);
}

public function getPaymentStatusAttribute(): string
{
    if (in_array($this->status, ['refunded', 'canceled', 'cancelled'], true)) {
        return $this->status;
    }

    $paid = (float) $this->paid_amount;
    $total = (float) ($this->total ?? 0);

    if ($total > 0 && $paid >= $total) {
        return 'paid';
    }

    return $paid > 0 ? 'partial' : 'unpaid';
}

private function paymentItemIds(mixed $value): array
{
    if ($value === null || $value === '') {
        return [];
    }

    if (is_string($value)) {
        $decoded = json_decode($value, true);
        $value = is_array($decoded) ? $decoded : [];
    }

    if (!is_array($value)) {
        return [];
    }

    return collect($value)
        ->map(fn ($id) => (int) $id)
        ->filter()
        ->values()
        ->all();
}
}
