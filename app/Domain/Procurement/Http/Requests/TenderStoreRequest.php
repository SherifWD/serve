<?php

namespace App\Domain\Procurement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TenderStoreRequest extends FormRequest
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
                Rule::unique('procurement_tenders', 'reference')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'title' => ['required', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['draft', 'open', 'closed', 'awarded', 'cancelled'])],
            'opening_date' => ['nullable', 'date'],
            'closing_date' => ['nullable', 'date', 'after_or_equal:opening_date'],
            'description' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
