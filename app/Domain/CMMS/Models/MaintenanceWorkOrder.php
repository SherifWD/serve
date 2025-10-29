<?php

namespace App\Domain\CMMS\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceWorkOrder extends TenantModel
{
    protected $table = 'cmms_work_orders';

    protected $fillable = [
        'tenant_id',
        'asset_id',
        'maintenance_plan_id',
        'reference',
        'status',
        'priority',
        'description',
        'scheduled_date',
        'completed_date',
        'metadata',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'completed_date' => 'date',
        'metadata' => 'array',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(MaintenancePlan::class, 'maintenance_plan_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(MaintenanceLog::class, 'work_order_id');
    }
}

