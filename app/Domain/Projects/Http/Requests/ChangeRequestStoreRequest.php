<?php

namespace App\Domain\Projects\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeRequestStoreRequest extends FormRequest
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
            'reference' => [
                'required',
                'string',
                'max:80',
                Rule::unique('projects_change_requests', 'reference')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'title' => ['required', 'string', 'max:255'],
            'change_type' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['draft', 'submitted', 'in_review', 'approved', 'rejected', 'implemented'])],
            'requested_by' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'requested_at' => ['nullable', 'date'],
            'target_date' => ['nullable', 'date'],
            'risk_level' => ['nullable', Rule::in(['low', 'medium', 'high'])],
            'impact_summary' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
