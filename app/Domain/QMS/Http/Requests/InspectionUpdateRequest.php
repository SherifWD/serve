<?php

namespace App\Domain\QMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InspectionUpdateRequest extends FormRequest
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
                'sometimes',
                'nullable',
                Rule::exists('qms_inspection_plans', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'item_id' => [
                'sometimes',
                'nullable',
                Rule::exists('erp_items', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'reference' => ['sometimes', 'nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'required', Rule::in(['scheduled', 'in_progress', 'passed', 'failed'])],
            'results' => ['sometimes', 'nullable', 'array'],
            'inspected_at' => ['sometimes', 'nullable', 'date'],
            'inspected_by' => ['sometimes', 'nullable', Rule::exists('users', 'id')],
        ];
    }
}

