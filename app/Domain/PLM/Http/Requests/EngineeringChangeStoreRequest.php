<?php

namespace App\Domain\PLM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EngineeringChangeStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        return [
            'product_design_id' => [
                'required',
                Rule::exists('plm_product_designs', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('plm_engineering_changes', 'code')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(['draft', 'submitted', 'approved', 'implemented', 'rejected'])],
            'effectivity_date' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}

