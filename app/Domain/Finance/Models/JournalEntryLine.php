<?php

namespace App\Domain\Finance\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalEntryLine extends TenantModel
{
    protected $table = 'finance_journal_entry_lines';

    protected $fillable = [
        'tenant_id',
        'journal_entry_id',
        'ledger_account_id',
        'debit',
        'credit',
        'memo',
    ];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function ledgerAccount(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class);
    }
}

