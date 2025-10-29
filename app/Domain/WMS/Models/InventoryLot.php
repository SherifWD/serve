<?php

namespace App\Domain\WMS\Models;

use App\Domain\ERP\Models\Item;
use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryLot extends TenantModel
{
    protected $table = 'wms_inventory_lots';

    protected $fillable = [
        'tenant_id',
        'item_id',
        'warehouse_id',
        'storage_bin_id',
        'lot_number',
        'quantity',
        'uom',
        'received_at',
        'expires_at',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'received_at' => 'date',
        'expires_at' => 'date',
        'metadata' => 'array',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function storageBin(): BelongsTo
    {
        return $this->belongsTo(StorageBin::class);
    }
}

