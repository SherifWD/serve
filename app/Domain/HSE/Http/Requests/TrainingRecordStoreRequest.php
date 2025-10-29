<?php

namespace App\Domain\HSE\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TrainingRecordStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'session_date' => ['nullable', 'date'],
            'trainer' => ['nullable', 'string', 'max:255'],
            'attendees' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
