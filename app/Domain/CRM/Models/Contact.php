<?php

namespace App\Domain\CRM\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contact extends TenantModel
{
    protected $table = 'crm_contacts';

    protected $fillable = [
        'tenant_id',
        'account_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'position',
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

