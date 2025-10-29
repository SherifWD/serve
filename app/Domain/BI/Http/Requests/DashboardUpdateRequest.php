<?php

namespace App\Domain\BI\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DashboardUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'layout' => ['sometimes', 'nullable', 'array'],
            'is_default' => ['sometimes', 'nullable', 'boolean'],
        ];
    }
}
