<?php

namespace App\Domain\Procurement\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends TenantModel
{
    protected $table = 'procurement_vendors';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'category',
        'status',
        'email',
        'phone',
        'address',
        'metadata',
    ];

    protected $casts = [
        'address' => 'array',
        'metadata' => 'array',
    ];

    public function tenders(): HasMany
    {
        return $this->hasMany(TenderResponse::class, 'vendor_id');
    }
}
