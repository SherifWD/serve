<?php

namespace App\Domain\PLM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DesignDocumentUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document_type' => ['sometimes', 'nullable', 'string', 'max:100'],
            'file_name' => ['sometimes', 'required', 'string', 'max:255'],
            'file_path' => ['sometimes', 'required', 'string', 'max:500'],
            'version' => ['sometimes', 'nullable', 'string', 'max:50'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}

