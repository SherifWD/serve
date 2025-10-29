<?php

namespace App\Domain\WMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransferOrderUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $transferOrder = $this->route('transfer_order');
        $reference = $this->input('reference', $transferOrder->reference ?? null);
        $id = $transferOrder?->id ?? null;

        return [
            'reference' => [
                'sometimes',
                'required',
                'string',
                'max:60',
                Rule::unique('wms_transfer_orders', 'reference')
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId))
                    ->ignore($id),
            ],
            'source_bin_id' => [
                'sometimes',
                'required',
                Rule::exists('wms_storage_bins', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'destination_bin_id' => [
                'sometimes',
                'required',
                Rule::exists('wms_storage_bins', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'item_id' => [
                'sometimes',
                'nullable',
                Rule::exists('erp_items', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'quantity' => ['sometimes', 'required', 'numeric', 'min:0.001'],
            'status' => ['sometimes', 'required', Rule::in(['draft', 'picking', 'picked', 'in_transit', 'completed', 'cancelled'])],
            'requested_at' => ['sometimes', 'nullable', 'date'],
            'completed_at' => ['sometimes', 'nullable', 'date'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}

