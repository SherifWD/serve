<?php

namespace App\Domain\HSE\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Action extends TenantModel
{
    protected $table = 'hse_actions';

    protected $fillable = [
        'tenant_id',
        'incident_id',
        'audit_id',
        'action_type',
        'description',
        'status',
        'due_date',
        'completed_date',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_date' => 'date',
    ];

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }
}
