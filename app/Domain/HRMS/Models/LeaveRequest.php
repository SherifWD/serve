<?php

namespace App\Domain\HRMS\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends TenantModel
{
    protected $table = 'hrms_leave_requests';

    protected $fillable = [
        'tenant_id',
        'worker_id',
        'leave_type',
        'start_date',
        'end_date',
        'status',
        'reason',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }
}
