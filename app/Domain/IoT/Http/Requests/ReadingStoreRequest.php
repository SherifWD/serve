<?php

namespace App\Domain\IoT\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReadingStoreRequest extends FormRequest
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
                'required',
                'integer',
                Rule::exists('iot_sensors', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'recorded_at' => ['required', 'date'],
            'value' => ['nullable', 'numeric'],
            'quality' => ['nullable', Rule::in(['good', 'warning', 'alarm'])],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
