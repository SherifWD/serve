<?php

namespace App\Domain\CMMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MaintenanceWorkOrderStoreRequest extends FormRequest
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
                'nullable',
                Rule::exists('cmms_assets', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'maintenance_plan_id' => [
                'nullable',
                Rule::exists('cmms_maintenance_plans', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'reference' => [
                'required',
                'string',
                'max:60',
                Rule::unique('cmms_work_orders', 'reference')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'status' => ['nullable', Rule::in(['scheduled', 'open', 'in_progress', 'completed', 'cancelled'])],
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high'])],
            'description' => ['nullable', 'string'],
            'scheduled_date' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
