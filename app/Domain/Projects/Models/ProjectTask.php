<?php

namespace App\Domain\Projects\Models;

use App\Domain\Shared\Models\TenantModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTask extends TenantModel
{
    protected $table = 'projects_tasks';

    protected $fillable = [
        'tenant_id',
        'project_id',
        'title',
        'description',
        'assignee_id',
        'status',
        'priority',
        'start_date',
        'due_date',
        'completed_at',
        'progress',
        'depends_on_task_id',
        'metadata',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'progress' => 'integer',
        'metadata' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function dependency(): BelongsTo
    {
        return $this->belongsTo(self::class, 'depends_on_task_id');
    }
}
