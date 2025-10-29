<?php

namespace App\Domain\QMS\Models;

use App\Domain\Shared\Models\TenantModel;

class Audit extends TenantModel
{
    protected $table = 'qms_audits';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'audit_type',
        'scheduled_date',
        'completed_date',
        'status',
        'findings',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'completed_date' => 'date',
        'findings' => 'array',
    ];
}

