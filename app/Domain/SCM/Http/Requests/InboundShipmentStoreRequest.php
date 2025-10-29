<?php

namespace App\Domain\SCM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InboundShipmentStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        return [
            'purchase_order_id' => [
                'required',
                Rule::exists('scm_purchase_orders', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'reference' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['pending', 'received', 'partial', 'closed'])],
            'arrival_date' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}

