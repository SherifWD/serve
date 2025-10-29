<?php

namespace App\Domain\SCM\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends TenantModel
{
    protected $table = 'scm_suppliers';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'contact_name',
        'email',
        'phone',
        'address',
        'status',
        'metadata',
    ];

    protected $casts = [
        'address' => 'array',
        'metadata' => 'array',
    ];

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'supplier_id');
    }
}

