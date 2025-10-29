<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens,HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'restaurant_id',
        'branch_id',
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class)
            ->withPivot(['tenant_id', 'assigned_at', 'metadata'])
            ->withTimestamps();
    }

    public function primaryRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function devices()
    {
        return $this->hasMany(Device::class);
    }

    public function types()
    {
        return $this->belongsToMany(Type::class, 'type_users');
    }

    public function tenantLinks()
    {
        return $this->hasMany(\App\Platform\Tenancy\Models\TenantUser::class);
    }

    public function tenants()
    {
        return $this->belongsToMany(
            \App\Platform\Tenancy\Models\Tenant::class,
            'tenant_users'
        )->withPivot(['is_primary', 'status', 'invited_at', 'accepted_at', 'settings'])
         ->withTimestamps();
    }

    public function primaryTenant()
    {
        return $this->tenants()->wherePivot('is_primary', true)->first();
    }
}
