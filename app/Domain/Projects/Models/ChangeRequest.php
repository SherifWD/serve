<?php

namespace App\Domain\Projects\Models;

use App\Domain\Shared\Models\TenantModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChangeRequest extends TenantModel
{
    protected $table = 'projects_change_requests';

    protected $fillable = [
        'tenant_id',
        'project_id',
        'reference',
        'title',
        'change_type',
        'status',
        'requested_by',
        'requested_at',
        'target_date',
        'risk_level',
        'impact_summary',
        'metadata',
    ];

    protected $casts = [
        'requested_at' => 'date',
        'target_date' => 'date',
        'metadata' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(ChangeApproval::class, 'change_request_id');
    }
}
