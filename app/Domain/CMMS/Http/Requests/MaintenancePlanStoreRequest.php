<?php

namespace App\Domain\CMMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MaintenancePlanStoreRequest extends FormRequest
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
                'required',
                Rule::exists('cmms_assets', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'frequency' => ['required', 'string', 'max:100'],
            'interval_days' => ['nullable', 'integer', 'min:1'],
            'tasks' => ['nullable', 'array'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ];
    }
}

