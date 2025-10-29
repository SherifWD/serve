<?php

namespace App\Domain\Budgeting\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CostCenter extends TenantModel
{
    protected $table = 'budgeting_cost_centers';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'department',
        'manager_name',
        'status',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class, 'cost_center_id');
    }

    public function actuals(): HasMany
    {
        return $this->hasMany(Actual::class, 'cost_center_id');
    }
}
