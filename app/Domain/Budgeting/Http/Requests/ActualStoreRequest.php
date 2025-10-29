<?php

namespace App\Domain\Budgeting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActualStoreRequest extends FormRequest
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
                'required',
                'integer',
                Rule::exists('budgeting_cost_centers', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'fiscal_year' => ['required', 'string', 'max:20'],
            'period' => ['required', 'string', 'max:20'],
            'actual_amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'source_reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
