<?php

namespace App\Domain\Communication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkflowRequestUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $workflow = $this->route('workflow_request');

        return [
            'reference' => [
                'sometimes',
                'required',
                'string',
                'max:80',
                Rule::unique('communication_workflow_requests', 'reference')
                    ->ignore($workflow?->id ?? null)
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'request_type' => ['sometimes', 'required', 'string', 'max:120'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'required', Rule::in(['pending', 'in_progress', 'approved', 'rejected', 'completed', 'cancelled'])],
            'priority' => ['sometimes', 'required', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'requester_id' => ['sometimes', 'nullable', 'integer', Rule::exists('users', 'id')],
            'assignee_id' => ['sometimes', 'nullable', 'integer', Rule::exists('users', 'id')],
            'requested_at' => ['sometimes', 'nullable', 'date'],
            'due_at' => ['sometimes', 'nullable', 'date'],
            'payload' => ['sometimes', 'nullable', 'array'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
