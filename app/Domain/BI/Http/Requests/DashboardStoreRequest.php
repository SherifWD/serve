<?php

namespace App\Domain\BI\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DashboardStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'layout' => ['nullable', 'array'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }
}
