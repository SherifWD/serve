<?php

namespace App\Platform\Tenancy\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TenantUserUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::in(['active', 'invited', 'suspended'])],
            'benefits' => ['nullable', 'array'],
            'benefits.*' => ['string', 'max:255'],
        ];
    }
}

