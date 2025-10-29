<?php

namespace App\Domain\SCM\Models;

use App\Domain\ERP\Models\Item;
use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemandPlan extends TenantModel
{
    protected $table = 'scm_demand_plans';

    protected $fillable = [
        'tenant_id',
        'item_id',
        'period',
        'forecast_quantity',
        'planning_strategy',
        'assumptions',
    ];

    protected $casts = [
        'forecast_quantity' => 'decimal:3',
        'assumptions' => 'array',
    ];

    public $timestamps = true;

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
