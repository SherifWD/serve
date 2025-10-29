<?php

namespace App\Domain\Finance\Models;

use App\Domain\Shared\Models\TenantModel;

class AccountReceivable extends TenantModel
{
    protected $table = 'finance_accounts_receivable';

    protected $fillable = [
        'tenant_id',
        'customer_name',
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

