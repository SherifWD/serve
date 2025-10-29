<?php

namespace App\Domain\Procurement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseRequestUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $request = $this->route('purchase_request');

        return [
            'reference' => [
                'sometimes',
                'required',
                'string',
                'max:80',
                Rule::unique('procurement_purchase_requests', 'reference')
                    ->ignore($request?->id ?? null)
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'requester_name' => ['sometimes', 'required', 'string', 'max:255'],
            'department' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'required', Rule::in(['draft', 'pending_approval', 'approved', 'rejected', 'fulfilled'])],
            'needed_by' => ['sometimes', 'nullable', 'date'],
            'total_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'justification' => ['sometimes', 'nullable', 'string'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
