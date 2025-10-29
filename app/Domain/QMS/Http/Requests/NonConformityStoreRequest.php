<?php

namespace App\Domain\QMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NonConformityStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        return [
            'inspection_id' => [
                'nullable',
                Rule::exists('qms_inspections', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'code' => [
                'required',
                'string',
                'max:60',
                Rule::unique('qms_non_conformities', 'code')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'severity' => ['nullable', Rule::in(['low', 'medium', 'high', 'critical'])],
            'status' => ['nullable', Rule::in(['open', 'in_progress', 'resolved', 'closed'])],
            'description' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}

