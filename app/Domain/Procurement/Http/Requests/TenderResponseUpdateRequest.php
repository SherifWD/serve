<?php

namespace App\Domain\Procurement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TenderResponseUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        return [
            'tender_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('procurement_tenders', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'vendor_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('procurement_vendors', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'response_date' => ['sometimes', 'nullable', 'date'],
            'status' => ['sometimes', 'required', Rule::in(['submitted', 'shortlisted', 'awarded', 'rejected'])],
            'amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
