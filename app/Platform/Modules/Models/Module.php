<?php

namespace App\Platform\Modules\Models;

use App\Platform\Tenancy\Models\SubscriptionPlan;
use App\Platform\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'category',
        'is_core',
        'has_mobile_app',
        'description',
        'config_schema',
    ];

    protected $casts = [
        'is_core' => 'boolean',
        'has_mobile_app' => 'boolean',
        'config_schema' => 'array',
    ];

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_modules')
            ->withPivot(['status', 'activated_at', 'deactivated_at', 'seat_limit', 'settings'])
            ->withTimestamps();
    }

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(SubscriptionPlan::class, 'subscription_plan_module')
            ->withPivot(['seat_limit', 'is_optional'])
            ->withTimestamps();
    }

    public function features(): HasMany
    {
        return $this->hasMany(ModuleFeature::class);
    }
}
