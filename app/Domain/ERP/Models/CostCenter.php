<?php

namespace App\Domain\ERP\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CostCenter extends TenantModel
{
    protected $table = 'erp_cost_centers';

    protected $fillable = [
        'tenant_id',
        'department_id',
        'code',
        'name',
        'status',
        'metadata',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}

