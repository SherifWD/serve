<?php

namespace App\Domain\Budgeting\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Actual extends TenantModel
{
    protected $table = 'budgeting_actuals';

    protected $fillable = [
        'tenant_id',
        'cost_center_id',
        'fiscal_year',
        'period',
        'actual_amount',
        'currency',
        'source_reference',
        'notes',
    ];

    protected $casts = [
        'actual_amount' => 'float',
    ];

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }
}
