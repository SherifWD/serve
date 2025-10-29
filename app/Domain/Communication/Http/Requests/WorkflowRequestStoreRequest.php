<?php

namespace App\Domain\Communication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkflowRequestStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        return [
            'reference' => [
                'required',
                'string',
                'max:80',
                Rule::unique('communication_workflow_requests', 'reference')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'request_type' => ['required', 'string', 'max:120'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(['pending', 'in_progress', 'approved', 'rejected', 'completed', 'cancelled'])],
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'requester_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'assignee_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'requested_at' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date'],
            'payload' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
