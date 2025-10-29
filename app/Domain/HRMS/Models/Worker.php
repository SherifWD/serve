<?php

namespace App\Domain\HRMS\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Worker extends TenantModel
{
    protected $table = 'hrms_workers';

    protected $fillable = [
        'tenant_id',
        'employee_number',
        'first_name',
        'last_name',
        'email',
        'phone',
        'employment_status',
        'hire_date',
        'metadata',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'metadata' => 'array',
    ];

    public function contracts(): HasMany
    {
        return $this->hasMany(EmploymentContract::class, 'worker_id');
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'worker_id');
    }

    public function payrollEntries(): HasMany
    {
        return $this->hasMany(PayrollEntry::class, 'worker_id');
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class, 'worker_id');
    }

    public function trainingAssignments(): HasMany
    {
        return $this->hasMany(TrainingAssignment::class, 'worker_id');
    }
}

