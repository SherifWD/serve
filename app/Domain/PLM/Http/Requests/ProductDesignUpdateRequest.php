<?php

namespace App\Domain\PLM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductDesignUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $designId = $this->route('product_design')?->id ?? null;

        return [
            'item_id' => [
                'sometimes',
                'nullable',
                Rule::exists('erp_items', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('plm_product_designs', 'code')
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId))
                    ->ignore($designId),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'version' => ['sometimes', 'nullable', 'string', 'max:50'],
            'lifecycle_state' => ['sometimes', 'required', Rule::in(['in_design', 'prototype', 'released', 'retired'])],
            'description' => ['sometimes', 'nullable', 'string'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
