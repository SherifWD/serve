<?php

namespace App\Domain\QMS\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NonConformity extends TenantModel
{
    protected $table = 'qms_non_conformities';

    protected $fillable = [
        'tenant_id',
        'inspection_id',
        'code',
        'severity',
        'status',
        'description',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }

    public function capaActions(): HasMany
    {
        return $this->hasMany(CapaAction::class, 'non_conformity_id');
    }
}

