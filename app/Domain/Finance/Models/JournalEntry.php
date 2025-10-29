<?php

namespace App\Domain\Finance\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalEntry extends TenantModel
{
    protected $table = 'finance_journal_entries';

    protected $fillable = [
        'tenant_id',
        'reference',
        'entry_date',
        'description',
        'status',
    ];

    protected $casts = [
        'entry_date' => 'date',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class, 'journal_entry_id');
    }
}

