<?php

namespace App\Domain\QMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CapaActionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        return [
            'non_conformity_id' => [
                'required',
                Rule::exists('qms_non_conformities', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'action_type' => ['nullable', Rule::in(['corrective', 'preventive'])],
            'description' => ['required', 'string'],
            'status' => ['nullable', Rule::in(['open', 'in_progress', 'verified', 'closed'])],
            'due_at' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}

