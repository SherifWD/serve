<?php

namespace App\Domain\HRMS\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollEntry extends TenantModel
{
    protected $table = 'hrms_payroll_entries';

    protected $fillable = [
        'tenant_id',
        'payroll_run_id',
        'worker_id',
        'gross_amount',
        'net_amount',
        'breakdown',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'breakdown' => 'array',
    ];

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }
}

