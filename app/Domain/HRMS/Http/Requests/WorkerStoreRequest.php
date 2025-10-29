<?php

namespace App\Domain\HRMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkerStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        return [
            'employee_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('hrms_workers', 'employee_number')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'employment_status' => ['nullable', Rule::in(['active', 'probation', 'terminated', 'on_leave'])],
            'hire_date' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}

