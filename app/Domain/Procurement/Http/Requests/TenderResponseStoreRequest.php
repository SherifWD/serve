<?php

namespace App\Domain\Procurement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TenderResponseStoreRequest extends FormRequest
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
                'required',
                'integer',
                Rule::exists('procurement_tenders', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'vendor_id' => [
                'nullable',
                'integer',
                Rule::exists('procurement_vendors', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'response_date' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(['submitted', 'shortlisted', 'awarded', 'rejected'])],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
