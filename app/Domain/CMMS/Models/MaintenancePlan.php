<?php

namespace App\Domain\CMMS\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenancePlan extends TenantModel
{
    protected $table = 'cmms_maintenance_plans';

    protected $fillable = [
        'tenant_id',
        'asset_id',
        'name',
        'frequency',
        'interval_days',
        'tasks',
        'status',
    ];

    protected $casts = [
        'tasks' => 'array',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(MaintenanceWorkOrder::class, 'maintenance_plan_id');
    }
}

