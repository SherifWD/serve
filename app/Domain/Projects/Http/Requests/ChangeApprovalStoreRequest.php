<?php

namespace App\Domain\Projects\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeApprovalStoreRequest extends FormRequest
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
                'required',
                'integer',
                Rule::exists('projects_change_requests', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'approver_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'status' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])],
            'role' => ['nullable', 'string', 'max:120'],
            'comments' => ['nullable', 'string'],
            'acted_at' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
