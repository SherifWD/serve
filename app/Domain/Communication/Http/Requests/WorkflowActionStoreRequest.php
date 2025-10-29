<?php

namespace App\Domain\Communication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkflowActionStoreRequest extends FormRequest
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
                'required',
                'integer',
                Rule::exists('communication_workflow_requests', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'actor_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'action' => ['required', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['recorded', 'pending', 'completed'])],
            'comments' => ['nullable', 'string'],
            'acted_at' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
