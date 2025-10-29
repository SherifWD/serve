<?php

namespace App\Domain\Visitor\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VisitorEntryStoreRequest extends FormRequest
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
                'required',
                'integer',
                Rule::exists('visitor_visitors', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'host_name' => ['nullable', 'string', 'max:255'],
            'host_department' => ['nullable', 'string', 'max:255'],
            'purpose' => ['nullable', 'string', 'max:255'],
            'scheduled_start' => ['nullable', 'date'],
            'scheduled_end' => ['nullable', 'date', 'after_or_equal:scheduled_start'],
            'check_in_at' => ['nullable', 'date'],
            'check_out_at' => ['nullable', 'date', 'after:check_in_at'],
            'badge_number' => ['nullable', 'string', 'max:60'],
            'status' => ['nullable', Rule::in(['scheduled', 'onsite', 'completed', 'cancelled', 'denied'])],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
