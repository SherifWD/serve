<?php

namespace App\Domain\CRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OpportunityUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        return [
            'account_id' => [
                'sometimes',
                'nullable',
                Rule::exists('crm_accounts', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'stage' => ['sometimes', 'required', Rule::in(['prospecting', 'proposal', 'negotiation', 'closed_won', 'closed_lost'])],
            'amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'close_date' => ['sometimes', 'nullable', 'date'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}

