<?php

namespace App\Domain\MES\Models;

use App\Domain\ERP\Models\Site;
use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionLine extends TenantModel
{
    protected $table = 'mes_production_lines';

    protected $fillable = [
        'tenant_id',
        'site_id',
        'code',
        'name',
        'status',
        'layout',
        'metadata',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function workCenters(): HasMany
    {
        return $this->hasMany(WorkCenter::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }
}

