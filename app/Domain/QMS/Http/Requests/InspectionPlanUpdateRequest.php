<?php

namespace App\Domain\QMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InspectionPlanUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $planId = $this->route('inspection_plan')?->id ?? null;

        return [
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('qms_inspection_plans', 'code')
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId))
                    ->ignore($planId),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'inspection_type' => ['sometimes', 'required', Rule::in(['incoming', 'in_process', 'final'])],
            'checklist' => ['sometimes', 'nullable', 'array'],
            'status' => ['sometimes', 'required', Rule::in(['active', 'inactive'])],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}

