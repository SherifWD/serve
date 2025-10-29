<?php

namespace App\Domain\ERP\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillOfMaterialLine extends TenantModel
{
    protected $table = 'erp_bom_lines';

    protected $fillable = [
        'tenant_id',
        'bom_id',
        'component_item_id',
        'quantity',
        'uom',
        'sequence',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'metadata' => 'array',
    ];

    public $timestamps = true;

    public function bom(): BelongsTo
    {
        return $this->belongsTo(BillOfMaterial::class, 'bom_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'component_item_id');
    }
}

