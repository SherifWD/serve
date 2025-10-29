<?php

namespace App\Domain\CRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContactUpdateRequest extends FormRequest
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
            'first_name' => ['sometimes', 'required', 'string', 'max:120'],
            'last_name' => ['sometimes', 'required', 'string', 'max:120'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'position' => ['sometimes', 'nullable', 'string', 'max:120'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}

