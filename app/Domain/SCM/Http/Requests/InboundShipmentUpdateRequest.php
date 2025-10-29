<?php

namespace App\Domain\SCM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InboundShipmentUpdateRequest extends FormRequest
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
                'sometimes',
                'required',
                Rule::exists('scm_purchase_orders', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'reference' => ['sometimes', 'nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'required', Rule::in(['pending', 'received', 'partial', 'closed'])],
            'arrival_date' => ['sometimes', 'nullable', 'date'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}

