<?php

namespace App\Domain\CMMS\Models;

use App\Domain\Shared\Models\TenantModel;

class SparePart extends TenantModel
{
    protected $table = 'cmms_spare_parts';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'uom',
        'quantity_on_hand',
        'reorder_level',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];
}

