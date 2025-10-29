<?php

namespace App\Domain\CRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServiceCaseStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        return [
            'account_id' => [
                'nullable',
                Rule::exists('crm_accounts', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'case_number' => ['required', 'string', 'max:60', 'unique:crm_service_cases,case_number'],
            'title' => ['required', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['open', 'working', 'resolved', 'closed'])],
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high'])],
            'description' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}

