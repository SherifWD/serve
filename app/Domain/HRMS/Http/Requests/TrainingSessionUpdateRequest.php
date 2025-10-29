<?php

namespace App\Domain\HRMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TrainingSessionUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'scheduled_date' => ['sometimes', 'nullable', 'date'],
            'status' => ['sometimes', 'required', Rule::in(['scheduled', 'completed', 'cancelled'])],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}

