<?php

namespace App\Domain\HRMS\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingSession extends TenantModel
{
    protected $table = 'hrms_training_sessions';

    protected $fillable = [
        'tenant_id',
        'title',
        'scheduled_date',
        'status',
        'metadata',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'metadata' => 'array',
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(TrainingAssignment::class, 'training_session_id');
    }
}

