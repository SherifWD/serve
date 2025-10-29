<?php

namespace App\Domain\Projects\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $project = $this->route('project');

        return [
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:60',
                Rule::unique('projects_projects', 'code')
                    ->ignore($project?->id ?? null)
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'status' => ['sometimes', 'required', Rule::in(['draft', 'active', 'on_hold', 'completed', 'archived'])],
            'stage' => ['sometimes', 'nullable', 'string', 'max:120'],
            'description' => ['sometimes', 'nullable', 'string'],
            'start_date' => ['sometimes', 'nullable', 'date'],
            'due_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_date'],
            'owner_id' => ['sometimes', 'nullable', 'integer', Rule::exists('users', 'id')],
            'budget_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
