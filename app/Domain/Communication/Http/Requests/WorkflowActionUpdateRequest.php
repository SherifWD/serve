<?php

namespace App\Domain\Communication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkflowActionUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        return [
            'workflow_request_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('communication_workflow_requests', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'actor_id' => ['sometimes', 'nullable', 'integer', Rule::exists('users', 'id')],
            'action' => ['sometimes', 'required', 'string', 'max:120'],
            'status' => ['sometimes', 'required', Rule::in(['recorded', 'pending', 'completed'])],
            'comments' => ['sometimes', 'nullable', 'string'],
            'acted_at' => ['sometimes', 'nullable', 'date'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
