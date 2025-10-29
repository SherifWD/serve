<?php

namespace App\Domain\Commerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $customer = $this->route('customer');

        return [
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:60',
                Rule::unique('commerce_customers', 'code')
                    ->ignore($customer?->id ?? null)
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:60'],
            'status' => ['sometimes', 'required', Rule::in(['active', 'inactive', 'prospect'])],
            'billing_address' => ['sometimes', 'nullable', 'array'],
            'shipping_address' => ['sometimes', 'nullable', 'array'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
