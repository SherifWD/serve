<?php

namespace App\Domain\Projects\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectTaskStoreRequest extends FormRequest
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
                'required',
                'integer',
                Rule::exists('projects_projects', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assignee_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'status' => ['nullable', Rule::in(['not_started', 'in_progress', 'completed', 'blocked'])],
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high'])],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'completed_at' => ['nullable', 'date'],
            'progress' => ['nullable', 'integer', 'min:0', 'max:100'],
            'depends_on_task_id' => [
                'nullable',
                'integer',
                Rule::exists('projects_tasks', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
