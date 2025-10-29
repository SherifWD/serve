<?php

namespace App\Domain\Visitor\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VisitorEntryUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        return [
            'visitor_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('visitor_visitors', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'host_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'host_department' => ['sometimes', 'nullable', 'string', 'max:255'],
            'purpose' => ['sometimes', 'nullable', 'string', 'max:255'],
            'scheduled_start' => ['sometimes', 'nullable', 'date'],
            'scheduled_end' => ['sometimes', 'nullable', 'date', 'after_or_equal:scheduled_start'],
            'check_in_at' => ['sometimes', 'nullable', 'date'],
            'check_out_at' => ['sometimes', 'nullable', 'date', 'after:check_in_at'],
            'badge_number' => ['sometimes', 'nullable', 'string', 'max:60'],
            'status' => ['sometimes', 'required', Rule::in(['scheduled', 'onsite', 'completed', 'cancelled', 'denied'])],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
