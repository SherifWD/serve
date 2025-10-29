<?php

namespace App\Platform\Tenancy\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TenantModuleFeatureUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['enabled', 'disabled'])],
            'settings' => ['nullable', 'array'],
        ];
    }
}

