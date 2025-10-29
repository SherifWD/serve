<?php

namespace App\Domain\HRMS\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends TenantModel
{
    protected $table = 'hrms_payroll_runs';

    protected $fillable = [
        'tenant_id',
        'reference',
        'period',
        'status',
        'pay_date',
        'gross_total',
        'net_total',
    ];

    protected $casts = [
        'pay_date' => 'date',
        'gross_total' => 'decimal:2',
        'net_total' => 'decimal:2',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(PayrollEntry::class, 'payroll_run_id');
    }
}

