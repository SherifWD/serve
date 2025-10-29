<?php

namespace App\Domain\Procurement\Models;

use App\Domain\Shared\Models\TenantModel;

class PurchaseRequest extends TenantModel
{
    protected $table = 'procurement_purchase_requests';

    protected $fillable = [
        'tenant_id',
        'reference',
        'requester_name',
        'department',
        'status',
        'needed_by',
        'total_amount',
        'justification',
        'metadata',
    ];

    protected $casts = [
        'needed_by' => 'date',
        'total_amount' => 'float',
        'metadata' => 'array',
    ];
}
