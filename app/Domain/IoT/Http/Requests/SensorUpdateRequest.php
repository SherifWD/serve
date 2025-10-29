<?php

namespace App\Domain\IoT\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SensorUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $sensor = $this->route('sensor');

        return [
            'device_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('iot_devices', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'tag' => [
                'sometimes',
                'required',
                'string',
                'max:120',
                Rule::unique('iot_sensors', 'tag')
                    ->ignore($sensor?->id ?? null)
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'unit' => ['sometimes', 'nullable', 'string', 'max:40'],
            'data_type' => ['sometimes', 'nullable', Rule::in(['float', 'integer', 'boolean', 'string'])],
            'threshold_min' => ['sometimes', 'nullable', 'numeric'],
            'threshold_max' => ['sometimes', 'nullable', 'numeric'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
