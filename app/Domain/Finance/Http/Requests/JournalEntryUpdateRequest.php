<?php

namespace App\Domain\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

class JournalEntryUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $entry = $this->route('journal_entry');
        $entryId = $entry?->id ?? null;
        $tenantId = app('tenant.context')->ensureTenant()->id;

        return [
            'reference' => [
                'sometimes',
                'required',
                'string',
                'max:60',
                Rule::unique('finance_journal_entries', 'reference')
                    ->ignore($entryId)
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'entry_date' => ['sometimes', 'required', 'date'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'required', Rule::in(['draft', 'posted', 'reversed'])],
            'lines' => ['sometimes', 'array', 'min:1'],
            'lines.*.id' => ['nullable', 'integer'],
            'lines.*.ledger_account_id' => [
                'nullable',
                'integer',
                Rule::exists('finance_ledger_accounts', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.memo' => ['nullable', 'string', 'max:255'],
            'lines.*._action' => ['nullable', Rule::in(['delete'])],
        ];
    }

    public function withValidator(ValidatorContract $validator): void
    {
        $entry = $this->route('journal_entry');
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $validator->after(function (ValidatorContract $validator) use ($entry, $tenantId) {
            $existingLines = collect();

            if ($entry) {
                $existingLines = $entry->lines()
                    ->where('tenant_id', $tenantId)
                    ->get(['id', 'debit', 'credit'])
                    ->mapWithKeys(fn ($line) => [
                        $line->id => [
                            'debit' => (float) $line->debit,
                            'credit' => (float) $line->credit,
                        ],
                    ]);
            }

            $payloadLines = collect($this->input('lines', []));
            $newLines = collect();

            foreach ($payloadLines as $index => $line) {
                $action = $line['_action'] ?? null;
                $lineId = $line['id'] ?? null;
                $debit = (float) ($line['debit'] ?? 0);
                $credit = (float) ($line['credit'] ?? 0);

                if ($action === 'delete') {
                    if ($lineId) {
                        $existingLines->forget($lineId);
                    }
                    continue;
                }

                if (!array_key_exists('ledger_account_id', $line) || empty($line['ledger_account_id'])) {
                    $validator->errors()->add("lines.$index.ledger_account_id", 'Select a ledger account for each journal line.');
                }

                if ($debit > 0 && $credit > 0) {
                    $validator->errors()->add("lines.$index.debit", 'A journal line cannot contain both debit and credit values.');
                }

                if ($debit <= 0 && $credit <= 0) {
                    $validator->errors()->add("lines.$index.debit", 'Provide a debit or credit amount for each journal line.');
                }

                if ($lineId) {
                    $existingLines[$lineId] = ['debit' => $debit, 'credit' => $credit];
                } else {
                    $newLines->push(['debit' => $debit, 'credit' => $credit]);
                }
            }

            $combinedLines = $existingLines->values();
            if ($newLines->isNotEmpty()) {
                $combinedLines = $combinedLines->concat($newLines);
            }

            if ($combinedLines->isEmpty()) {
                $validator->errors()->add('lines', 'Journal entries must retain at least one line.');
                return;
            }

            $totalDebit = $combinedLines->sum(fn ($line) => (float) ($line['debit'] ?? 0));
            $totalCredit = $combinedLines->sum(fn ($line) => (float) ($line['credit'] ?? 0));

            if ($totalDebit <= 0 || $totalCredit <= 0) {
                $validator->errors()->add('lines', 'Journal entries must contain debit and credit amounts greater than zero.');
            }

            if (abs($totalDebit - $totalCredit) > 0.01) {
                $validator->errors()->add('lines', 'Journal entry debits and credits must balance.');
            }
        });
    }
}
