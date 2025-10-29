<?php

namespace App\Domain\CMMS\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends TenantModel
{
    protected $table = 'cmms_assets';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'asset_type',
        'status',
        'location',
        'metadata',
        'commissioned_at',
    ];

    protected $casts = [
        'location' => 'array',
        'metadata' => 'array',
        'commissioned_at' => 'date',
    ];

    public function maintenancePlans(): HasMany
    {
        return $this->hasMany(MaintenancePlan::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(MaintenanceWorkOrder::class, 'asset_id');
    }
}

