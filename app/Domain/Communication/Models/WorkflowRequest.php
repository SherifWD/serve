<?php

namespace App\Domain\Communication\Models;

use App\Domain\Shared\Models\TenantModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowRequest extends TenantModel
{
    protected $table = 'communication_workflow_requests';

    protected $fillable = [
        'tenant_id',
        'reference',
        'request_type',
        'title',
        'description',
        'status',
        'priority',
        'requester_id',
        'assignee_id',
        'requested_at',
        'due_at',
        'payload',
        'metadata',
    ];

    protected $casts = [
        'requested_at' => 'date',
        'due_at' => 'date',
        'payload' => 'array',
        'metadata' => 'array',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(WorkflowAction::class, 'workflow_request_id');
    }
}
