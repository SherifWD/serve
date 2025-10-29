<?php

namespace App\Domain\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccountPayableUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $record = $this->route('account_payable');
        $id = $record?->id ?? null;

        return [
            'vendor_name' => ['sometimes', 'required', 'string', 'max:255'],
            'invoice_number' => [
                'sometimes',
                'required',
                'string',
                'max:60',
                Rule::unique('finance_accounts_payable', 'invoice_number')
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId))
                    ->ignore($id),
            ],
            'invoice_date' => ['sometimes', 'required', 'date'],
            'due_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:invoice_date'],
            'amount' => ['sometimes', 'required', 'numeric', 'min:0'],
            'status' => ['sometimes', 'required', Rule::in(['open', 'paid', 'cancelled'])],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}

