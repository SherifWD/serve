<?php

namespace App\Domain\Communication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AnnouncementUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'body' => ['sometimes', 'required', 'string'],
            'priority' => ['sometimes', 'nullable', Rule::in(['low', 'normal', 'high', 'critical'])],
            'status' => ['sometimes', 'required', Rule::in(['draft', 'scheduled', 'published', 'expired'])],
            'publish_at' => ['sometimes', 'nullable', 'date'],
            'expires_at' => ['sometimes', 'nullable', 'date', 'after:publish_at'],
            'audiences' => ['sometimes', 'nullable', 'array'],
            'attachments' => ['sometimes', 'nullable', 'array'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
