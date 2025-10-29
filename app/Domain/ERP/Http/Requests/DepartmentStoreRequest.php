<?php

namespace App\Domain\ERP\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepartmentStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        return [
            'site_id' => [
                'required',
                Rule::exists('erp_sites', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('erp_departments', 'code')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'status' => ['nullable', 'in:active,inactive'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}

