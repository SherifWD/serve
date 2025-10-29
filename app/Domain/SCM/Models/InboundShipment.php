<?php

namespace App\Domain\SCM\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InboundShipment extends TenantModel
{
    protected $table = 'scm_inbound_shipments';

    protected $fillable = [
        'tenant_id',
        'purchase_order_id',
        'reference',
        'status',
        'arrival_date',
        'metadata',
    ];

    protected $casts = [
        'arrival_date' => 'date',
        'metadata' => 'array',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }
}

