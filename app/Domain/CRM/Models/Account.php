<?php

namespace App\Domain\CRM\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends TenantModel
{
    protected $table = 'crm_accounts';

    protected $fillable = [
        'tenant_id',
        'name',
        'industry',
        'status',
        'address',
        'metadata',
    ];

    protected $casts = [
        'address' => 'array',
        'metadata' => 'array',
    ];

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }

    public function serviceCases(): HasMany
    {
        return $this->hasMany(ServiceCase::class);
    }
}

