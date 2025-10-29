<?php

namespace App\Domain\HRMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkerUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $workerId = $this->route('worker')?->id ?? null;

        return [
            'employee_number' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
                Rule::unique('hrms_workers', 'employee_number')
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId))
                    ->ignore($workerId),
            ],
            'first_name' => ['sometimes', 'required', 'string', 'max:120'],
            'last_name' => ['sometimes', 'required', 'string', 'max:120'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'employment_status' => ['sometimes', 'required', Rule::in(['active', 'probation', 'terminated', 'on_leave'])],
            'hire_date' => ['sometimes', 'nullable', 'date'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}

