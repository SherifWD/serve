<?php

namespace App\Domain\Commerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        return [
            'order_number' => [
                'required',
                'string',
                'max:80',
                Rule::unique('commerce_orders', 'order_number')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'customer_id' => [
                'nullable',
                'integer',
                Rule::exists('commerce_customers', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'channel' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['pending', 'paid', 'fulfilled', 'cancelled', 'refunded'])],
            'placed_at' => ['nullable', 'date'],
            'fulfilled_at' => ['nullable', 'date', 'after_or_equal:placed_at'],
            'currency' => ['nullable', 'string', 'size:3'],
            'metadata' => ['nullable', 'array'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sku' => ['nullable', 'string', 'max:80'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.line_total' => ['nullable', 'numeric', 'min:0'],
            'items.*.metadata' => ['nullable', 'array'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'shipping_fee' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'subtotal' => ['nullable', 'numeric', 'min:0'],
            'total' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
