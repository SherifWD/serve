<?php

namespace App\Domain\Finance\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\Finance\Models\JournalEntryLine */
class JournalEntryLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ledger_account_id' => $this->ledger_account_id,
            'debit' => $this->debit,
            'credit' => $this->credit,
            'memo' => $this->memo,
            'ledger_account' => LedgerAccountResource::make($this->whenLoaded('ledgerAccount')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

