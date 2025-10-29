<?php

namespace App\Domain\ERP\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends TenantModel
{
    protected $table = 'erp_departments';

    protected $fillable = [
        'tenant_id',
        'site_id',
        'code',
        'name',
        'status',
        'metadata',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function costCenters(): HasMany
    {
        return $this->hasMany(CostCenter::class);
    }
}

