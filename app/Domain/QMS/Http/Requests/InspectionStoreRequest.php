<?php

namespace App\Domain\QMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InspectionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        return [
            'inspection_plan_id' => [
                'nullable',
                Rule::exists('qms_inspection_plans', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'item_id' => [
                'nullable',
                Rule::exists('erp_items', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'reference' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['scheduled', 'in_progress', 'passed', 'failed'])],
            'results' => ['nullable', 'array'],
            'inspected_at' => ['nullable', 'date'],
            'inspected_by' => ['nullable', Rule::exists('users', 'id')],
        ];
    }
}

