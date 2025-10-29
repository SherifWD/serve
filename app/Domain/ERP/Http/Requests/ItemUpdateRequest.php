<?php

namespace App\Domain\ERP\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ItemUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $itemId = $this->route('item')?->id ?? null;

        return [
            'category_id' => [
                'sometimes',
                'nullable',
                Rule::exists('erp_item_categories', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('erp_items', 'code')
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId))
                    ->ignore($itemId),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'sku' => ['sometimes', 'nullable', 'string', 'max:100'],
            'type' => ['sometimes', 'required', Rule::in(['manufactured', 'purchased', 'service'])],
            'uom' => ['sometimes', 'required', 'string', 'max:20'],
            'status' => ['sometimes', 'required', Rule::in(['active', 'inactive'])],
            'standard_cost' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'list_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'attributes' => ['sometimes', 'nullable', 'array'],
        ];
    }
}

