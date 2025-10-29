<?php

namespace App\Domain\ERP\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends TenantModel
{
    protected $table = 'erp_items';

    protected $fillable = [
        'tenant_id',
        'category_id',
        'sku',
        'code',
        'name',
        'type',
        'uom',
        'status',
        'standard_cost',
        'list_price',
        'attributes',
    ];

    protected $casts = [
        'standard_cost' => 'decimal:4',
        'list_price' => 'decimal:4',
        'attributes' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'category_id');
    }

    public function bomHeaders(): HasMany
    {
        return $this->hasMany(BillOfMaterial::class, 'item_id');
    }
}

