<?php

namespace App\Domain\IoT\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeviceStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        return [
            'device_key' => [
                'required',
                'string',
                'max:120',
                Rule::unique('iot_devices', 'device_key')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'device_type' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['inactive', 'active', 'maintenance', 'offline'])],
            'location' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
            'installed_at' => ['nullable', 'date'],
            'last_heartbeat_at' => ['nullable', 'date'],
        ];
    }
}
