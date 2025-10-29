<?php

namespace App\Platform\Tenancy\Models;

use App\Models\User;
use App\Platform\Modules\Models\Module;
use App\Platform\Modules\Models\ModuleFeature;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Tenant extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'uid',
        'code',
        'name',
        'domain',
        'industry',
        'timezone',
        'status',
        'billing_email',
        'phone',
        'activated_at',
        'suspended_at',
        'trial_ends_at',
        'settings',
        'metadata',
    ];

    protected $casts = [
        'settings' => 'array',
        'metadata' => 'array',
        'activated_at' => 'datetime',
        'suspended_at' => 'datetime',
        'trial_ends_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $tenant): void {
            if (empty($tenant->uid)) {
                $tenant->uid = (string) Str::uuid();
            }

            if (empty($tenant->code)) {
                $tenant->code = strtoupper(Str::random(8));
            }
        });
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_users')
            ->using(TenantUser::class)
            ->withPivot(['is_primary', 'status', 'invited_at', 'accepted_at', 'settings'])
            ->withTimestamps();
    }

    public function tenantUsers(): HasMany
    {
        return $this->hasMany(TenantUser::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscription::class);
    }

    public function activeSubscription(): ?TenantSubscription
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->latest('starts_at')
            ->first();
    }

    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'tenant_modules')
            ->using(TenantModule::class)
            ->withPivot(['status', 'activated_at', 'deactivated_at', 'seat_limit', 'settings'])
            ->withTimestamps();
    }

    public function moduleFeatures(): BelongsToMany
    {
        return $this->belongsToMany(ModuleFeature::class, 'tenant_module_features')
            ->withPivot(['status', 'settings'])
            ->withTimestamps();
    }

    public function discounts(): HasMany
    {
        return $this->hasMany(SubscriptionDiscount::class);
    }
}
