<?php

namespace App\Domain\Budgeting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActualUpdateRequest extends FormRequest
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
            'actual_amount' => ['sometimes', 'required', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'nullable', 'string', 'size:3'],
            'source_reference' => ['sometimes', 'nullable', 'string', 'max:120'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
