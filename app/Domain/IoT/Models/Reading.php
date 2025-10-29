<?php

namespace App\Domain\IoT\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reading extends TenantModel
{
    protected $table = 'iot_readings';

    protected $fillable = [
        'tenant_id',
        'sensor_id',
        'recorded_at',
        'value',
        'quality',
        'metadata',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'value' => 'float',
        'metadata' => 'array',
    ];

    public function sensor(): BelongsTo
    {
        return $this->belongsTo(Sensor::class, 'sensor_id');
    }
}
