<?php

namespace App\Domain\ERP\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends TenantModel
{
    protected $table = 'erp_sites';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'status',
        'timezone',
        'address',
        'settings',
    ];

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function productionLines(): HasMany
    {
        return $this->hasMany(\App\Domain\MES\Models\ProductionLine::class, 'site_id');
    }
}

