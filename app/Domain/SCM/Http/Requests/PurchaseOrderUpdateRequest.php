<?php

namespace App\Domain\SCM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseOrderUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $purchaseOrder = $this->route('purchase_order');
        $poId = $purchaseOrder?->id ?? null;
        $poNumber = $this->input('po_number', $purchaseOrder->po_number ?? null);

        return [
            'supplier_id' => [
                'sometimes',
                'required',
                Rule::exists('scm_suppliers', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'po_number' => [
                'sometimes',
                'required',
                'string',
                'max:60',
                Rule::unique('scm_purchase_orders', 'po_number')
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId))
                    ->ignore($poId),
            ],
            'order_date' => ['sometimes', 'nullable', 'date'],
            'expected_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:order_date'],
            'status' => ['sometimes', 'required', Rule::in(['draft', 'approved', 'sent', 'received', 'closed', 'cancelled'])],
            'metadata' => ['sometimes', 'nullable', 'array'],
            'lines' => ['sometimes', 'array', 'min:1'],
            'lines.*.id' => ['nullable', 'integer'],
            'lines.*.item_id' => [
                'nullable',
                Rule::exists('erp_items', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.quantity' => ['required_with:lines', 'numeric', 'min:0.001'],
            'lines.*.uom' => ['nullable', 'string', 'max:20'],
            'lines.*.unit_price' => ['required_with:lines', 'numeric', 'min:0'],
            'lines.*._action' => ['nullable', Rule::in(['delete'])],
        ];
    }
}

