<?php

namespace App\Domain\HSE\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IncidentStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        return [
            'reference' => [
                'required',
                'string',
                'max:60',
                Rule::unique('hse_incidents', 'reference')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'title' => ['required', 'string', 'max:255'],
            'incident_date' => ['required', 'date'],
            'severity' => ['nullable', Rule::in(['low', 'medium', 'high', 'critical'])],
            'status' => ['nullable', Rule::in(['open', 'investigating', 'resolved', 'closed'])],
            'description' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
