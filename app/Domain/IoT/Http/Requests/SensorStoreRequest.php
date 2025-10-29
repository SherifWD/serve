<?php

namespace App\Domain\IoT\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SensorStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        return [
            'device_id' => [
                'required',
                'integer',
                Rule::exists('iot_devices', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'tag' => [
                'required',
                'string',
                'max:120',
                Rule::unique('iot_sensors', 'tag')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:40'],
            'data_type' => ['nullable', Rule::in(['float', 'integer', 'boolean', 'string'])],
            'threshold_min' => ['nullable', 'numeric'],
            'threshold_max' => ['nullable', 'numeric'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
