<?php

namespace App\Domain\WMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransferOrderStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        return [
            'reference' => [
                'required',
                'string',
                'max:60',
                Rule::unique('wms_transfer_orders', 'reference')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'source_bin_id' => [
                'required',
                Rule::exists('wms_storage_bins', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'destination_bin_id' => [
                'required',
                Rule::exists('wms_storage_bins', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'item_id' => [
                'nullable',
                Rule::exists('erp_items', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'status' => ['nullable', Rule::in(['draft', 'picking', 'picked', 'in_transit', 'completed', 'cancelled'])],
            'requested_at' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}

