<?php

namespace App\Domain\ERP\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ItemStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        return [
            'category_id' => [
                'nullable',
                Rule::exists('erp_item_categories', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('erp_items', 'code')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100'],
            'type' => ['required', Rule::in(['manufactured', 'purchased', 'service'])],
            'uom' => ['required', 'string', 'max:20'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'standard_cost' => ['nullable', 'numeric', 'min:0'],
            'list_price' => ['nullable', 'numeric', 'min:0'],
            'attributes' => ['nullable', 'array'],
        ];
    }
}

