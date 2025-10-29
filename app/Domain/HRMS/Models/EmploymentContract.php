<?php

namespace App\Domain\HRMS\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmploymentContract extends TenantModel
{
    protected $table = 'hrms_employment_contracts';

    protected $fillable = [
        'tenant_id',
        'worker_id',
        'contract_type',
        'start_date',
        'end_date',
        'salary',
        'currency',
        'metadata',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'salary' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }
}

