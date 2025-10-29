<?php

namespace App\Domain\Communication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AnnouncementStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'priority' => ['nullable', Rule::in(['low', 'normal', 'high', 'critical'])],
            'status' => ['nullable', Rule::in(['draft', 'scheduled', 'published', 'expired'])],
            'publish_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:publish_at'],
            'audiences' => ['nullable', 'array'],
            'attachments' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
