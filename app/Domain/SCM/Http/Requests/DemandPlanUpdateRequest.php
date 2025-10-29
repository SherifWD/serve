<?php

namespace App\Domain\SCM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DemandPlanUpdateRequest extends FormRequest
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
                'sometimes',
                'nullable',
                Rule::exists('erp_items', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'period' => ['sometimes', 'required', 'string', 'max:20'],
            'forecast_quantity' => ['sometimes', 'required', 'numeric', 'min:0'],
            'planning_strategy' => ['sometimes', 'nullable', 'string', 'max:100'],
            'assumptions' => ['sometimes', 'nullable', 'array'],
        ];
    }
}

