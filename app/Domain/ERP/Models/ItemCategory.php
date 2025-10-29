<?php

namespace App\Domain\ERP\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemCategory extends TenantModel
{
    protected $table = 'erp_item_categories';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'description',
    ];

    public $timestamps = true;

    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'category_id');
    }
}

