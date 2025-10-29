<?php

namespace App\Domain\HRMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PayrollRunStoreRequest extends FormRequest
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
                Rule::unique('hrms_payroll_runs', 'reference')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'period' => ['required', 'string', 'max:20'],
            'status' => ['nullable', Rule::in(['draft', 'processing', 'paid', 'closed'])],
            'pay_date' => ['nullable', 'date'],
            'entries' => ['nullable', 'array'],
            'entries.*.worker_id' => [
                'required_with:entries',
                Rule::exists('hrms_workers', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'entries.*.gross_amount' => ['required_with:entries', 'numeric', 'min:0'],
            'entries.*.net_amount' => ['required_with:entries', 'numeric', 'min:0'],
            'entries.*.breakdown' => ['nullable', 'array'],
        ];
    }
}

