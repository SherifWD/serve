<?php

namespace App\Domain\PLM\Models;

use App\Domain\ERP\Models\Item;
use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductDesign extends TenantModel
{
    protected $table = 'plm_product_designs';

    protected $fillable = [
        'tenant_id',
        'item_id',
        'code',
        'name',
        'version',
        'lifecycle_state',
        'description',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function changes(): HasMany
    {
        return $this->hasMany(EngineeringChange::class, 'product_design_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(DesignDocument::class, 'product_design_id');
    }
}

