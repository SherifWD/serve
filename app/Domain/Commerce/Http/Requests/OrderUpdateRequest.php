<?php

namespace App\Domain\Commerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $order = $this->route('order');

        return [
            'order_number' => [
                'sometimes',
                'required',
                'string',
                'max:80',
                Rule::unique('commerce_orders', 'order_number')
                    ->ignore($order?->id ?? null)
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'customer_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('commerce_customers', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'channel' => ['sometimes', 'nullable', 'string', 'max:120'],
            'status' => ['sometimes', 'required', Rule::in(['pending', 'paid', 'fulfilled', 'cancelled', 'refunded'])],
            'placed_at' => ['sometimes', 'nullable', 'date'],
            'fulfilled_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:placed_at'],
            'currency' => ['sometimes', 'nullable', 'string', 'size:3'],
            'metadata' => ['sometimes', 'nullable', 'array'],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.sku' => ['nullable', 'string', 'max:80'],
            'items.*.name' => ['required_without:items.*._action', 'string', 'max:255'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.line_total' => ['nullable', 'numeric', 'min:0'],
            'items.*.metadata' => ['nullable', 'array'],
            'items.*._action' => ['nullable', Rule::in(['delete'])],
            'discount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'shipping_fee' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'tax' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'subtotal' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'total' => ['sometimes', 'nullable', 'numeric', 'min:0'],
        ];
    }
}
