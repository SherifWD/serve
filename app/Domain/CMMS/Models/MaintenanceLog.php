<?php

namespace App\Domain\CMMS\Models;

use App\Domain\Shared\Models\TenantModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceLog extends TenantModel
{
    protected $table = 'cmms_maintenance_logs';

    protected $fillable = [
        'tenant_id',
        'work_order_id',
        'notes',
        'logged_at',
        'logged_by',
        'metadata',
    ];

    protected $casts = [
        'logged_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(MaintenanceWorkOrder::class, 'work_order_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by');
    }
}

