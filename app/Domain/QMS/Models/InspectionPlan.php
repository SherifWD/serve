<?php

namespace App\Domain\QMS\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InspectionPlan extends TenantModel
{
    protected $table = 'qms_inspection_plans';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'inspection_type',
        'checklist',
        'status',
        'metadata',
    ];

    protected $casts = [
        'checklist' => 'array',
        'metadata' => 'array',
    ];

    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class, 'inspection_plan_id');
    }
}

