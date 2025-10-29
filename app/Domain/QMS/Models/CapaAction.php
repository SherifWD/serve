<?php

namespace App\Domain\QMS\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CapaAction extends TenantModel
{
    protected $table = 'qms_capa_actions';

    protected $fillable = [
        'tenant_id',
        'non_conformity_id',
        'action_type',
        'description',
        'status',
        'due_at',
        'completed_at',
        'metadata',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function nonConformity(): BelongsTo
    {
        return $this->belongsTo(NonConformity::class, 'non_conformity_id');
    }
}

