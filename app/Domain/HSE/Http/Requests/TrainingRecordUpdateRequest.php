<?php

namespace App\Domain\HSE\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TrainingRecordUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'session_date' => ['sometimes', 'nullable', 'date'],
            'trainer' => ['sometimes', 'nullable', 'string', 'max:255'],
            'attendees' => ['sometimes', 'nullable', 'array'],
            'metadata' => ['sometimes', 'array'],
        ];
    }
}
