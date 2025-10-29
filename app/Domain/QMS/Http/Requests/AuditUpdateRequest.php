<?php

namespace App\Domain\QMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AuditUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $auditId = $this->route('audit')?->id ?? null;

        return [
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:60',
                Rule::unique('qms_audits', 'code')
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId))
                    ->ignore($auditId),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'audit_type' => ['sometimes', 'required', Rule::in(['internal', 'external', 'certification'])],
            'scheduled_date' => ['sometimes', 'nullable', 'date'],
            'completed_date' => ['sometimes', 'nullable', 'date'],
            'status' => ['sometimes', 'required', Rule::in(['scheduled', 'in_progress', 'completed', 'cancelled'])],
            'findings' => ['sometimes', 'nullable', 'array'],
        ];
    }
}

