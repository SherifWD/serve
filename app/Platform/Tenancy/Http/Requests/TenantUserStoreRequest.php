<?php

namespace App\Platform\Tenancy\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TenantUserStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['active', 'invited', 'suspended'])],
            'benefits' => ['nullable', 'array'],
            'benefits.*' => ['string', 'max:255'],
        ];
    }
}

