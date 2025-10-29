<?php

namespace App\Domain\Projects\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectTaskUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        return [
            'project_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('projects_projects', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'assignee_id' => ['sometimes', 'nullable', 'integer', Rule::exists('users', 'id')],
            'status' => ['sometimes', 'required', Rule::in(['not_started', 'in_progress', 'completed', 'blocked'])],
            'priority' => ['sometimes', 'required', Rule::in(['low', 'medium', 'high'])],
            'start_date' => ['sometimes', 'nullable', 'date'],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'completed_at' => ['sometimes', 'nullable', 'date'],
            'progress' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:100'],
            'depends_on_task_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('projects_tasks', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
