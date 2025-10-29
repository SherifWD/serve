<?php

namespace App\Domain\Procurement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseRequestStoreRequest extends FormRequest
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
                'max:80',
                Rule::unique('procurement_purchase_requests', 'reference')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'requester_name' => ['required', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['draft', 'pending_approval', 'approved', 'rejected', 'fulfilled'])],
            'needed_by' => ['nullable', 'date'],
            'total_amount' => ['nullable', 'numeric', 'min:0'],
            'justification' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
