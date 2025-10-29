<?php

namespace App\Domain\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

class JournalEntryStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        return [
            'reference' => [
                'required',
                'string',
                'max:60',
                Rule::unique('finance_journal_entries', 'reference')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'entry_date' => ['required', 'date'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(['draft', 'posted', 'reversed'])],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.ledger_account_id' => [
                'required',
                Rule::exists('finance_ledger_accounts', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.memo' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            $lines = collect($this->input('lines', []));

            if ($lines->isEmpty()) {
                return;
            }

            $totalDebit = 0.0;
            $totalCredit = 0.0;

            foreach ($lines as $index => $line) {
                $debit = (float) ($line['debit'] ?? 0);
                $credit = (float) ($line['credit'] ?? 0);

                if ($debit > 0 && $credit > 0) {
                    $validator->errors()->add("lines.$index.debit", 'A journal line cannot contain both debit and credit values.');
                }

                if ($debit <= 0 && $credit <= 0) {
                    $validator->errors()->add("lines.$index.debit", 'Provide a debit or credit amount for each journal line.');
                }

                $totalDebit += $debit;
                $totalCredit += $credit;
            }

            if ($totalDebit <= 0 || $totalCredit <= 0) {
                $validator->errors()->add('lines', 'Journal entries must contain debit and credit amounts greater than zero.');
            }

            if (abs($totalDebit - $totalCredit) > 0.01) {
                $validator->errors()->add('lines', 'Journal entry debits and credits must balance.');
            }
        });
    }
}
