<?php

namespace App\Domain\HSE\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Incident extends TenantModel
{
    protected $table = 'hse_incidents';

    protected $fillable = [
        'tenant_id',
        'reference',
        'title',
        'incident_date',
        'severity',
        'status',
        'description',
        'metadata',
    ];

    protected $casts = [
        'incident_date' => 'date',
    ];

    public function actions(): HasMany
    {
        return $this->hasMany(Action::class, 'incident_id');
    }
}
