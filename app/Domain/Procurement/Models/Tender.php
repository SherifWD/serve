<?php

namespace App\Domain\Procurement\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tender extends TenantModel
{
    protected $table = 'procurement_tenders';

    protected $fillable = [
        'tenant_id',
        'reference',
        'title',
        'status',
        'opening_date',
        'closing_date',
        'description',
        'metadata',
    ];

    protected $casts = [
        'opening_date' => 'date',
        'closing_date' => 'date',
        'metadata' => 'array',
    ];

    public function responses(): HasMany
    {
        return $this->hasMany(TenderResponse::class, 'tender_id');
    }
}
