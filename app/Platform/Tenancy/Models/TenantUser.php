<?php

namespace App\Platform\Tenancy\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class TenantUser extends Pivot
{
    protected $table = 'tenant_users';

    protected $casts = [
        'is_primary' => 'boolean',
        'invited_at' => 'datetime',
        'accepted_at' => 'datetime',
        'settings' => 'array',
    ];

    protected $fillable = [
        'tenant_id',
        'user_id',
        'is_primary',
        'status',
        'invited_at',
        'accepted_at',
        'settings',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}

