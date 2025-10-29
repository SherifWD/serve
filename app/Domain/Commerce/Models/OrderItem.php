<?php

namespace App\Domain\Commerce\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends TenantModel
{
    protected $table = 'commerce_order_items';

    protected $fillable = [
        'tenant_id',
        'order_id',
        'sku',
        'name',
        'quantity',
        'unit_price',
        'line_total',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'float',
        'unit_price' => 'float',
        'line_total' => 'float',
        'metadata' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
