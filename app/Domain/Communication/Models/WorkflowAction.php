<?php

namespace App\Domain\Communication\Models;

use App\Domain\Shared\Models\TenantModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowAction extends TenantModel
{
    protected $table = 'communication_workflow_actions';

    protected $fillable = [
        'tenant_id',
        'workflow_request_id',
        'actor_id',
        'action',
        'status',
        'comments',
        'acted_at',
        'metadata',
    ];

    protected $casts = [
        'acted_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function workflowRequest(): BelongsTo
    {
        return $this->belongsTo(WorkflowRequest::class, 'workflow_request_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
