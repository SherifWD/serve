<?php

namespace App\Domain\PLM\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EngineeringChange extends TenantModel
{
    protected $table = 'plm_engineering_changes';

    protected $fillable = [
        'tenant_id',
        'product_design_id',
        'code',
        'title',
        'description',
        'status',
        'effectivity_date',
        'requested_by',
        'approved_by',
        'metadata',
    ];

    protected $casts = [
        'effectivity_date' => 'date',
        'metadata' => 'array',
    ];

    public function design(): BelongsTo
    {
        return $this->belongsTo(ProductDesign::class, 'product_design_id');
    }
}

