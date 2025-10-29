<?php

namespace App\Domain\Projects\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeApprovalUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        return [
            'change_request_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('projects_change_requests', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'approver_id' => ['sometimes', 'nullable', 'integer', Rule::exists('users', 'id')],
            'status' => ['sometimes', 'required', Rule::in(['pending', 'approved', 'rejected'])],
            'role' => ['sometimes', 'nullable', 'string', 'max:120'],
            'comments' => ['sometimes', 'nullable', 'string'],
            'acted_at' => ['sometimes', 'nullable', 'date'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
