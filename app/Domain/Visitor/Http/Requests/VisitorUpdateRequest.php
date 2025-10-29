<?php

namespace App\Domain\Visitor\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VisitorUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $visitor = $this->route('visitor');

        return [
            'full_name' => ['sometimes', 'required', 'string', 'max:255'],
            'company' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:60'],
            'id_type' => ['sometimes', 'nullable', 'string', 'max:120'],
            'id_number' => [
                'sometimes',
                'nullable',
                'string',
                'max:120',
                Rule::unique('visitor_visitors', 'id_number')
                    ->ignore($visitor?->id ?? null)
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'status' => ['sometimes', 'required', Rule::in(['invited', 'checked_in', 'checked_out', 'blocked'])],
            'is_watchlisted' => ['sometimes', 'nullable', 'boolean'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
