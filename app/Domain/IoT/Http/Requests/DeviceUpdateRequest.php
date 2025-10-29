<?php

namespace App\Domain\IoT\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeviceUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $device = $this->route('device');

        return [
            'device_key' => [
                'sometimes',
                'required',
                'string',
                'max:120',
                Rule::unique('iot_devices', 'device_key')
                    ->ignore($device?->id ?? null)
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'device_type' => ['sometimes', 'nullable', 'string', 'max:120'],
            'status' => ['sometimes', 'required', Rule::in(['inactive', 'active', 'maintenance', 'offline'])],
            'location' => ['sometimes', 'nullable', 'array'],
            'metadata' => ['sometimes', 'nullable', 'array'],
            'installed_at' => ['sometimes', 'nullable', 'date'],
            'last_heartbeat_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
