<?php

namespace App\Platform\Tenancy\Models;

use App\Platform\Modules\Models\Module;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionDiscount extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'module_id',
        'subscription_plan_id',
        'name',
        'type',
        'value',
        'stackable',
        'starts_at',
        'ends_at',
        'reason',
        'meta',
    ];

    protected $casts = [
        'value' => 'float',
        'stackable' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'meta' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }
}

