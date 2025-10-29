<?php

namespace App\Domain\CMMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MaintenanceLogUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        return [
            'work_order_id' => [
                'sometimes',
                'required',
                Rule::exists('cmms_work_orders', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'notes' => ['sometimes', 'nullable', 'string'],
            'logged_at' => ['sometimes', 'nullable', 'date'],
            'logged_by' => ['sometimes', 'nullable', Rule::exists('users', 'id')],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}

