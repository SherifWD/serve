<?php

namespace App\Domain\SCM\Models;

use App\Domain\ERP\Models\Item;
use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderLine extends TenantModel
{
    protected $table = 'scm_purchase_order_lines';

    protected $fillable = [
        'tenant_id',
        'purchase_order_id',
        'item_id',
        'description',
        'quantity',
        'uom',
        'unit_price',
        'line_total',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public $timestamps = true;

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}

