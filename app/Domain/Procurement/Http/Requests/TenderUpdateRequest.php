<?php

namespace App\Domain\Procurement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TenderUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $tender = $this->route('tender');

        return [
            'reference' => [
                'sometimes',
                'required',
                'string',
                'max:80',
                Rule::unique('procurement_tenders', 'reference')
                    ->ignore($tender?->id ?? null)
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'status' => ['sometimes', 'required', Rule::in(['draft', 'open', 'closed', 'awarded', 'cancelled'])],
            'opening_date' => ['sometimes', 'nullable', 'date'],
            'closing_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:opening_date'],
            'description' => ['sometimes', 'nullable', 'string'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
