<?php

namespace App\Domain\IoT\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sensor extends TenantModel
{
    protected $table = 'iot_sensors';

    protected $fillable = [
        'tenant_id',
        'device_id',
        'tag',
        'name',
        'unit',
        'data_type',
        'threshold_min',
        'threshold_max',
        'metadata',
    ];

    protected $casts = [
        'threshold_min' => 'float',
        'threshold_max' => 'float',
        'metadata' => 'array',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_id');
    }

    public function readings(): HasMany
    {
        return $this->hasMany(Reading::class, 'sensor_id');
    }
}
