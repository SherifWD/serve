<?php

namespace App\Domain\CMMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MaintenanceLogStoreRequest extends FormRequest
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
                'required',
                Rule::exists('cmms_work_orders', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'notes' => ['nullable', 'string'],
            'logged_at' => ['nullable', 'date'],
            'logged_by' => ['nullable', Rule::exists('users', 'id')],
            'metadata' => ['nullable', 'array'],
        ];
    }
}

