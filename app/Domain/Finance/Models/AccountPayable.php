<?php

namespace App\Domain\Finance\Models;

use App\Domain\Shared\Models\TenantModel;

class AccountPayable extends TenantModel
{
    protected $table = 'finance_accounts_payable';

    protected $fillable = [
        'tenant_id',
        'vendor_name',
        'invoice_number',
        'invoice_date',
        'due_date',
        'amount',
        'status',
        'metadata',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'amount' => 'decimal:2',
        'metadata' => 'array',
    ];
}

