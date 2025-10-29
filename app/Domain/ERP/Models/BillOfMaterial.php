<?php

namespace App\Domain\ERP\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillOfMaterial extends TenantModel
{
    protected $table = 'erp_bom_headers';

    protected $fillable = [
        'tenant_id',
        'item_id',
        'code',
        'revision',
        'effective_from',
        'effective_to',
        'status',
        'metadata',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'metadata' => 'array',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BillOfMaterialLine::class, 'bom_id');
    }
}

