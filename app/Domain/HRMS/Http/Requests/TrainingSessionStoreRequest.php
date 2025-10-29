<?php

namespace App\Domain\HRMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TrainingSessionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'scheduled_date' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(['scheduled', 'completed', 'cancelled'])],
            'metadata' => ['nullable', 'array'],
        ];
    }
}

