<?php

namespace App\Domain\Budgeting\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends TenantModel
{
    protected $table = 'budgeting_budgets';

    protected $fillable = [
        'tenant_id',
        'cost_center_id',
        'fiscal_year',
        'period',
        'status',
        'planned_amount',
        'approved_amount',
        'forecast_amount',
        'currency',
        'assumptions',
    ];

    protected $casts = [
        'planned_amount' => 'float',
        'approved_amount' => 'float',
        'forecast_amount' => 'float',
        'assumptions' => 'array',
    ];

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }
}
