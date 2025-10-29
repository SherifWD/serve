<?php

namespace App\Platform\Tenancy\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TenantUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->route('tenant')->id ?? null;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'status' => ['sometimes', 'required', Rule::in(['trial', 'active', 'suspended', 'canceled'])],
            'industry' => ['nullable', 'string', 'max:255'],
            'billing_email' => ['sometimes', 'required', 'email', 'max:255'],
        ];
    }
}

