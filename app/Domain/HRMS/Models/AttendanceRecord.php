<?php

namespace App\Domain\HRMS\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends TenantModel
{
    protected $table = 'hrms_attendance_records';

    protected $fillable = [
        'tenant_id',
        'worker_id',
        'attendance_date',
        'check_in_at',
        'check_out_at',
        'status',
        'metadata',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }
}

