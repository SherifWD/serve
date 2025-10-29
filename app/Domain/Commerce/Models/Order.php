<?php

namespace App\Domain\Commerce\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends TenantModel
{
    protected $table = 'commerce_orders';

    protected $fillable = [
        'tenant_id',
        'order_number',
        'customer_id',
        'channel',
        'status',
        'subtotal',
        'discount',
        'shipping_fee',
        'tax',
        'total',
        'currency',
        'placed_at',
        'fulfilled_at',
        'metadata',
    ];

    protected $casts = [
        'subtotal' => 'float',
        'discount' => 'float',
        'shipping_fee' => 'float',
        'tax' => 'float',
        'total' => 'float',
        'placed_at' => 'datetime',
        'fulfilled_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }
}
