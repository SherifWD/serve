<?php

namespace App\Domain\Commerce\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends TenantModel
{
    protected $table = 'commerce_customers';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'email',
        'phone',
        'status',
        'billing_address',
        'shipping_address',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'billing_address' => 'array',
        'shipping_address' => 'array',
        'metadata' => 'array',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }
}
