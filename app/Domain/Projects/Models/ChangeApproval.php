<?php

namespace App\Domain\Projects\Models;

use App\Domain\Shared\Models\TenantModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChangeApproval extends TenantModel
{
    protected $table = 'projects_change_approvals';

    protected $fillable = [
        'tenant_id',
        'change_request_id',
        'approver_id',
        'status',
        'role',
        'comments',
        'acted_at',
        'metadata',
    ];

    protected $casts = [
        'acted_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function changeRequest(): BelongsTo
    {
        return $this->belongsTo(ChangeRequest::class, 'change_request_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
