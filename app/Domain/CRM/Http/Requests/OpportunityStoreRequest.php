<?php

namespace App\Domain\CRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OpportunityStoreRequest extends FormRequest
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
                'nullable',
                Rule::exists('crm_accounts', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'stage' => ['nullable', Rule::in(['prospecting', 'proposal', 'negotiation', 'closed_won', 'closed_lost'])],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'close_date' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}

