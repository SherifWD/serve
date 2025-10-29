<?php

namespace App\Domain\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccountReceivableStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'invoice_number' => [
                'required',
                'string',
                'max:60',
                Rule::unique('finance_accounts_receivable', 'invoice_number')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'status' => ['nullable', Rule::in(['open', 'paid', 'cancelled'])],
            'metadata' => ['nullable', 'array'],
        ];
    }
}

