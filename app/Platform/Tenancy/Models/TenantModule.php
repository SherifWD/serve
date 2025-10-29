<?php

namespace App\Platform\Tenancy\Models;

use App\Platform\Modules\Models\Module;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class TenantModule extends Pivot
{
    protected $table = 'tenant_modules';

    protected $casts = [
        'activated_at' => 'datetime',
        'deactivated_at' => 'datetime',
        'settings' => 'array',
    ];

    protected $fillable = [
        'tenant_id',
        'module_id',
        'status',
        'activated_at',
        'deactivated_at',
        'seat_limit',
        'settings',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}

