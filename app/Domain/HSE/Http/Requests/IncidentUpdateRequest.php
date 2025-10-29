<?php

namespace App\Domain\HSE\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IncidentUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $incident = $this->route('incident');
        $incidentId = $incident?->id ?? null;
        $tenantId = app('tenant.context')->ensureTenant()->id;

        return [
            'reference' => [
                'sometimes',
                'required',
                'string',
                'max:60',
                Rule::unique('hse_incidents', 'reference')
                    ->ignore($incidentId)
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'incident_date' => ['sometimes', 'required', 'date'],
            'severity' => ['sometimes', 'required', Rule::in(['low', 'medium', 'high', 'critical'])],
            'status' => ['sometimes', 'required', Rule::in(['open', 'investigating', 'resolved', 'closed'])],
            'description' => ['sometimes', 'nullable', 'string'],
            'metadata' => ['sometimes', 'array'],
        ];
    }
}
