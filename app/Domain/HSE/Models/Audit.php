<?php

namespace App\Domain\HSE\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Audit extends TenantModel
{
    protected $table = 'hse_audits';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'scheduled_date',
        'completed_date',
        'status',
        'findings',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'completed_date' => 'date',
        'findings' => 'array',
    ];

    public function actions(): HasMany
    {
        return $this->hasMany(Action::class, 'audit_id');
    }
}
