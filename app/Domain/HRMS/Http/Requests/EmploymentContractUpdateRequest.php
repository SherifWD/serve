<?php

namespace App\Domain\HRMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmploymentContractUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        return [
            'worker_id' => [
                'sometimes',
                'required',
                Rule::exists('hrms_workers', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'contract_type' => ['sometimes', 'required', Rule::in(['permanent', 'temporary', 'contractor'])],
            'start_date' => ['sometimes', 'nullable', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_date'],
            'salary' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'nullable', 'string', 'size:3'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}

