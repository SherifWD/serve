<?php

namespace App\Domain\Projects\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeRequestUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $changeRequest = $this->route('change_request');

        return [
            'project_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('projects_projects', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'reference' => [
                'sometimes',
                'required',
                'string',
                'max:80',
                Rule::unique('projects_change_requests', 'reference')
                    ->ignore($changeRequest?->id ?? null)
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'change_type' => ['sometimes', 'nullable', 'string', 'max:120'],
            'status' => ['sometimes', 'required', Rule::in(['draft', 'submitted', 'in_review', 'approved', 'rejected', 'implemented'])],
            'requested_by' => ['sometimes', 'nullable', 'integer', Rule::exists('users', 'id')],
            'requested_at' => ['sometimes', 'nullable', 'date'],
            'target_date' => ['sometimes', 'nullable', 'date'],
            'risk_level' => ['sometimes', 'nullable', Rule::in(['low', 'medium', 'high'])],
            'impact_summary' => ['sometimes', 'nullable', 'string'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
