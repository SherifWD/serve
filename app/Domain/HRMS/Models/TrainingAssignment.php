<?php

namespace App\Domain\HRMS\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingAssignment extends TenantModel
{
    protected $table = 'hrms_training_assignments';

    protected $fillable = [
        'tenant_id',
        'training_session_id',
        'worker_id',
        'status',
    ];

    public function trainingSession(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class, 'training_session_id');
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }
}

