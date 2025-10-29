<?php

namespace App\Domain\ERP\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BillOfMaterialStoreRequest extends FormRequest
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
                'required',
                Rule::exists('erp_items', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'code' => [
                'required',
                'string',
                'max:100',
            ],
            'revision' => [
                'nullable',
                'string',
                'max:10',
                Rule::unique('erp_bom_headers', 'revision')
                    ->where(fn ($query) => $query
                        ->where('tenant_id', $tenantId)
                        ->where('code', $this->input('code'))),
            ],
            'status' => ['nullable', Rule::in(['draft', 'active', 'archived'])],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'metadata' => ['nullable', 'array'],
            'components' => ['required', 'array', 'min:1'],
            'components.*.component_item_id' => [
                'required',
                Rule::exists('erp_items', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'components.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'components.*.uom' => ['nullable', 'string', 'max:20'],
            'components.*.sequence' => ['nullable', 'integer', 'min:1'],
            'components.*.metadata' => ['nullable', 'array'],
        ];
    }
}

