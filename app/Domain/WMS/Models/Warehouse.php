<?php

namespace App\Domain\WMS\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends TenantModel
{
    protected $table = 'wms_warehouses';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'status',
        'address',
        'metadata',
    ];

    protected $casts = [
        'address' => 'array',
        'metadata' => 'array',
    ];

    public function storageBins(): HasMany
    {
        return $this->hasMany(StorageBin::class, 'warehouse_id');
    }
}

