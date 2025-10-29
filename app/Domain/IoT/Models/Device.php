<?php

namespace App\Domain\IoT\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends TenantModel
{
    protected $table = 'iot_devices';

    protected $fillable = [
        'tenant_id',
        'device_key',
        'name',
        'device_type',
        'status',
        'location',
        'metadata',
        'installed_at',
        'last_heartbeat_at',
    ];

    protected $casts = [
        'location' => 'array',
        'metadata' => 'array',
        'installed_at' => 'date',
        'last_heartbeat_at' => 'datetime',
    ];

    public function sensors(): HasMany
    {
        return $this->hasMany(Sensor::class, 'device_id');
    }
}
