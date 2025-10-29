<?php

namespace App\Domain\BI\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kpi extends TenantModel
{
    protected $table = 'bi_kpis';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'category',
        'unit',
        'config',
    ];

    protected $casts = [
        'config' => 'array',
    ];

    public function snapshots(): HasMany
    {
        return $this->hasMany(DataSnapshot::class, 'kpi_id');
    }
}

