<?php

namespace App\Domain\Visitor\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VisitorStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        return [
            'full_name' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:60'],
            'id_type' => ['nullable', 'string', 'max:120'],
            'id_number' => [
                'nullable',
                'string',
                'max:120',
                Rule::unique('visitor_visitors', 'id_number')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'status' => ['nullable', Rule::in(['invited', 'checked_in', 'checked_out', 'blocked'])],
            'is_watchlisted' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
