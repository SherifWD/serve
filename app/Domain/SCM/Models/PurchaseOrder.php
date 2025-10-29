<?php

namespace App\Domain\SCM\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends TenantModel
{
    protected $table = 'scm_purchase_orders';

    protected $fillable = [
        'tenant_id',
        'supplier_id',
        'po_number',
        'status',
        'order_date',
        'expected_date',
        'subtotal',
        'tax_total',
        'grand_total',
        'metadata',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class, 'purchase_order_id');
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(InboundShipment::class, 'purchase_order_id');
    }
}

