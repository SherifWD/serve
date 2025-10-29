<?php

namespace App\Domain\ERP\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BillOfMaterialUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $bom = $this->route('bom');
        $bomId = $bom?->id ?? null;
        $code = $this->input('code', $bom->code ?? null);

        return [
            'item_id' => [
                'sometimes',
                'required',
                Rule::exists('erp_items', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:100',
            ],
            'revision' => [
                'sometimes',
                'required',
                'string',
                'max:10',
                Rule::unique('erp_bom_headers', 'revision')
                    ->where(fn ($query) => $query
                        ->where('tenant_id', $tenantId)
                        ->where('code', $code))
                    ->ignore($bomId),
            ],
            'status' => ['sometimes', 'required', Rule::in(['draft', 'active', 'archived'])],
            'effective_from' => ['sometimes', 'nullable', 'date'],
            'effective_to' => ['sometimes', 'nullable', 'date', 'after_or_equal:effective_from'],
            'metadata' => ['sometimes', 'nullable', 'array'],
            'components' => ['sometimes', 'array', 'min:1'],
            'components.*.id' => ['nullable', 'integer'],
            'components.*.component_item_id' => [
                'required_with:components',
                Rule::exists('erp_items', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'components.*.quantity' => ['required_with:components', 'numeric', 'min:0.0001'],
            'components.*.uom' => ['nullable', 'string', 'max:20'],
            'components.*.sequence' => ['nullable', 'integer', 'min:1'],
            'components.*.metadata' => ['nullable', 'array'],
            'components.*._action' => ['nullable', Rule::in(['delete'])],
        ];
    }
}
