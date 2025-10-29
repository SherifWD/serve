<?php

namespace App\Domain\CRM\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceCase extends TenantModel
{
    protected $table = 'crm_service_cases';

    protected $fillable = [
        'tenant_id',
        'account_id',
        'case_number',
        'title',
        'status',
        'priority',
        'description',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}

