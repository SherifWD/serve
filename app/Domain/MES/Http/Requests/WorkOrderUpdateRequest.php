<?php

namespace App\Domain\MES\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkOrderUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $workOrderId = $this->route('work_order')?->id ?? null;

        return [
            'item_id' => [
                'sometimes',
                'required',
                Rule::exists('erp_items', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'production_line_id' => [
                'sometimes',
                'nullable',
                Rule::exists('mes_production_lines', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('mes_work_orders', 'code')
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId))
                    ->ignore($workOrderId),
            ],
            'status' => ['sometimes', 'required', Rule::in(['planned', 'released', 'in_progress', 'completed', 'closed'])],
            'quantity' => ['sometimes', 'required', 'numeric', 'min:0.0001'],
            'quantity_completed' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'planned_start_at' => ['sometimes', 'nullable', 'date'],
            'planned_end_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:planned_start_at'],
            'actual_start_at' => ['sometimes', 'nullable', 'date'],
            'actual_end_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:actual_start_at'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
