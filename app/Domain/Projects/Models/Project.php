<?php

namespace App\Domain\Projects\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

class Project extends TenantModel
{
    protected $table = 'projects_projects';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'status',
        'stage',
        'description',
        'start_date',
        'due_date',
        'owner_id',
        'budget_amount',
        'metadata',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'budget_amount' => 'float',
        'metadata' => 'array',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class, 'project_id');
    }

    public function changeRequests(): HasMany
    {
        return $this->hasMany(ChangeRequest::class, 'project_id');
    }
}
