<?php

namespace App\Domain\CRM\Models;

use App\Domain\Shared\Models\TenantModel;

class Lead extends TenantModel
{
    protected $table = 'crm_leads';

    protected $fillable = [
        'tenant_id',
        'company_name',
        'contact_name',
        'email',
        'phone',
        'status',
        'source',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];
}

