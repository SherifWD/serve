<?php

namespace App\Domain\CRM\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Opportunity extends TenantModel
{
    protected $table = 'crm_opportunities';

    protected $fillable = [
        'tenant_id',
        'account_id',
        'name',
        'stage',
        'amount',
        'close_date',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'close_date' => 'date',
        'metadata' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}

