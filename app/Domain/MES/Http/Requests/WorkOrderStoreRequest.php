<?php

namespace App\Domain\MES\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkOrderStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        return [
            'item_id' => [
                'required',
                Rule::exists('erp_items', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'production_line_id' => [
                'nullable',
                Rule::exists('mes_production_lines', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('mes_work_orders', 'code')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'status' => ['nullable', Rule::in(['planned', 'released', 'in_progress', 'completed', 'closed'])],
            'quantity' => ['required', 'numeric', 'min:0.0001'],
            'planned_start_at' => ['nullable', 'date'],
            'planned_end_at' => ['nullable', 'date', 'after_or_equal:planned_start_at'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}

