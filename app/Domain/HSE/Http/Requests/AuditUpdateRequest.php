<?php

namespace App\Domain\HSE\Http\Requests;

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
        $audit = $this->route('audit');
        $auditId = $audit?->id ?? null;
        $tenantId = app('tenant.context')->ensureTenant()->id;

        return [
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:60',
                Rule::unique('hse_audits', 'code')
                    ->ignore($auditId)
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'scheduled_date' => ['sometimes', 'nullable', 'date'],
            'completed_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:scheduled_date'],
            'status' => ['sometimes', 'required', Rule::in(['scheduled', 'in_progress', 'completed', 'cancelled'])],
            'findings' => ['sometimes', 'array'],
        ];
    }
}
