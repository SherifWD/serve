<?php

namespace App\Domain\MES\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionEvent extends TenantModel
{
    protected $table = 'mes_production_events';

    public $timestamps = true;

    protected $fillable = [
        'tenant_id',
        'work_order_id',
        'machine_id',
        'recorded_by',
        'event_type',
        'event_timestamp',
        'payload',
    ];

    protected $casts = [
        'event_timestamp' => 'datetime',
        'payload' => 'array',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }
}

