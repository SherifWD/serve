<?php

namespace App\Domain\HSE\Models;

use App\Domain\Shared\Models\TenantModel;

class TrainingRecord extends TenantModel
{
    protected $table = 'hse_training_records';

    protected $fillable = [
        'tenant_id',
        'title',
        'session_date',
        'trainer',
        'attendees',
        'metadata',
    ];

    protected $casts = [
        'session_date' => 'date',
        'attendees' => 'array',
        'metadata' => 'array',
    ];
}
