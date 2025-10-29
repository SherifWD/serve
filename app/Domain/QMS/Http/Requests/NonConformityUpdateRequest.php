<?php

namespace App\Domain\QMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NonConformityUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $nonConformityId = $this->route('non_conformity')?->id ?? null;

        return [
            'inspection_id' => [
                'sometimes',
                'nullable',
                Rule::exists('qms_inspections', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:60',
                Rule::unique('qms_non_conformities', 'code')
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId))
                    ->ignore($nonConformityId),
            ],
            'severity' => ['sometimes', 'required', Rule::in(['low', 'medium', 'high', 'critical'])],
            'status' => ['sometimes', 'required', Rule::in(['open', 'in_progress', 'resolved', 'closed'])],
            'description' => ['sometimes', 'nullable', 'string'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}

