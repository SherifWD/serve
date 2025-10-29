<?php

namespace App\Domain\WMS\Models;

use App\Domain\ERP\Models\Item;
use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferOrder extends TenantModel
{
    protected $table = 'wms_transfer_orders';

    protected $fillable = [
        'tenant_id',
        'reference',
        'source_bin_id',
        'destination_bin_id',
        'item_id',
        'quantity',
        'status',
        'requested_at',
        'completed_at',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'requested_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function sourceBin(): BelongsTo
    {
        return $this->belongsTo(StorageBin::class, 'source_bin_id');
    }

    public function destinationBin(): BelongsTo
    {
        return $this->belongsTo(StorageBin::class, 'destination_bin_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}

