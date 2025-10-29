<?php

namespace App\Domain\MES\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Machine extends TenantModel
{
    protected $table = 'mes_machines';

    protected $fillable = [
        'tenant_id',
        'work_center_id',
        'code',
        'name',
        'serial_number',
        'status',
        'specs',
    ];

    protected $casts = [
        'specs' => 'array',
    ];

    public function workCenter(): BelongsTo
    {
        return $this->belongsTo(WorkCenter::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ProductionEvent::class);
    }
}

