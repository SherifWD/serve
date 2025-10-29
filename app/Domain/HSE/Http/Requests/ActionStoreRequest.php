<?php

namespace App\Domain\HSE\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        return [
            'incident_id' => [
                'nullable',
                'integer',
                'required_without:audit_id',
                Rule::exists('hse_incidents', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'audit_id' => [
                'nullable',
                'integer',
                'required_without:incident_id',
                Rule::exists('hse_audits', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'action_type' => ['required', Rule::in(['corrective', 'preventive'])],
            'description' => ['required', 'string'],
            'status' => ['nullable', Rule::in(['open', 'in_progress', 'verified', 'closed'])],
            'due_date' => ['nullable', 'date'],
            'completed_date' => ['nullable', 'date'],
        ];
    }
}
