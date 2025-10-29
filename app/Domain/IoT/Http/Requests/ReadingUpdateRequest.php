<?php

namespace App\Domain\IoT\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReadingUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        return [
            'sensor_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('iot_sensors', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'recorded_at' => ['sometimes', 'required', 'date'],
            'value' => ['sometimes', 'nullable', 'numeric'],
            'quality' => ['sometimes', 'nullable', Rule::in(['good', 'warning', 'alarm'])],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
