<?php

namespace App\Domain\Finance\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LedgerAccount extends TenantModel
{
    protected $table = 'finance_ledger_accounts';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'account_type',
        'parent_code',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class, 'ledger_account_id');
    }
}

