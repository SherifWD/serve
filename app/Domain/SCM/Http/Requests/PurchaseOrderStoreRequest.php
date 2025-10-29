<?php

namespace App\Domain\SCM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseOrderStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        return [
            'supplier_id' => [
                'required',
                Rule::exists('scm_suppliers', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'po_number' => [
                'required',
                'string',
                'max:60',
                Rule::unique('scm_purchase_orders', 'po_number')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'order_date' => ['nullable', 'date'],
            'expected_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'status' => ['nullable', Rule::in(['draft', 'approved', 'sent', 'received', 'closed', 'cancelled'])],
            'metadata' => ['nullable', 'array'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => [
                'nullable',
                Rule::exists('erp_items', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'lines.*.uom' => ['nullable', 'string', 'max:20'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }
}

