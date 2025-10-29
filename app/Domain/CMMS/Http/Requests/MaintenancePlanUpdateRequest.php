<?php

namespace App\Domain\CMMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MaintenancePlanUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        return [
            'asset_id' => [
                'sometimes',
                'required',
                Rule::exists('cmms_assets', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'frequency' => ['sometimes', 'required', 'string', 'max:100'],
            'interval_days' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'tasks' => ['sometimes', 'nullable', 'array'],
            'status' => ['sometimes', 'required', Rule::in(['active', 'inactive'])],
        ];
    }
}

