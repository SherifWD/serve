<?php

namespace App\Domain\WMS\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StorageBin extends TenantModel
{
    protected $table = 'wms_storage_bins';

    protected $fillable = [
        'tenant_id',
        'warehouse_id',
        'code',
        'zone',
        'status',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function lots(): HasMany
    {
        return $this->hasMany(InventoryLot::class, 'storage_bin_id');
    }
}

