<?php

namespace App\Domain\CMMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MaintenanceWorkOrderUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $workOrder = $this->route('work_order');
        $id = $workOrder?->id ?? null;

        return [
            'asset_id' => [
                'sometimes',
                'nullable',
                Rule::exists('cmms_assets', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'maintenance_plan_id' => [
                'sometimes',
                'nullable',
                Rule::exists('cmms_maintenance_plans', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'reference' => [
                'sometimes',
                'required',
                'string',
                'max:60',
                Rule::unique('cmms_work_orders', 'reference')
                    ->ignore($id)
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'status' => ['sometimes', 'required', Rule::in(['scheduled', 'open', 'in_progress', 'completed', 'cancelled'])],
            'priority' => ['sometimes', 'required', Rule::in(['low', 'medium', 'high'])],
            'description' => ['sometimes', 'nullable', 'string'],
            'scheduled_date' => ['sometimes', 'nullable', 'date'],
            'completed_date' => ['sometimes', 'nullable', 'date'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
