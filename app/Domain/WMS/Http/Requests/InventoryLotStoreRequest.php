<?php

namespace App\Domain\WMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryLotStoreRequest extends FormRequest
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
                'nullable',
                Rule::exists('erp_items', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'warehouse_id' => [
                'required',
                Rule::exists('wms_warehouses', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'storage_bin_id' => [
                'required',
                Rule::exists('wms_storage_bins', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'lot_number' => ['nullable', 'string', 'max:100'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'uom' => ['nullable', 'string', 'max:20'],
            'received_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:received_at'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}

