<?php

namespace App\Domain\WMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryLotUpdateRequest extends FormRequest
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
            'warehouse_id' => [
                'sometimes',
                'required',
                Rule::exists('wms_warehouses', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'storage_bin_id' => [
                'sometimes',
                'required',
                Rule::exists('wms_storage_bins', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'lot_number' => ['sometimes', 'nullable', 'string', 'max:100'],
            'quantity' => ['sometimes', 'required', 'numeric', 'min:0'],
            'uom' => ['sometimes', 'nullable', 'string', 'max:20'],
            'received_at' => ['sometimes', 'nullable', 'date'],
            'expires_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:received_at'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}

