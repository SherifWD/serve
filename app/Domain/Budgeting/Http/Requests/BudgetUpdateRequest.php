<?php

namespace App\Domain\Budgeting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BudgetUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        return [
            'cost_center_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('budgeting_cost_centers', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'fiscal_year' => ['sometimes', 'required', 'string', 'max:20'],
            'period' => ['sometimes', 'required', 'string', 'max:20'],
            'status' => ['sometimes', 'required', Rule::in(['draft', 'submitted', 'approved', 'locked'])],
            'planned_amount' => ['sometimes', 'required', 'numeric', 'min:0'],
            'approved_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'forecast_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'nullable', 'string', 'size:3'],
            'assumptions' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
