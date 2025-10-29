<?php

namespace App\Domain\Visitor\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Visitor extends TenantModel
{
    protected $table = 'visitor_visitors';

    protected $fillable = [
        'tenant_id',
        'full_name',
        'company',
        'email',
        'phone',
        'id_type',
        'id_number',
        'status',
        'is_watchlisted',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_watchlisted' => 'boolean',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(VisitorEntry::class, 'visitor_id');
    }
}
