<?php

namespace App\Domain\WMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorageBinStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        return [
            'warehouse_id' => [
                'required',
                Rule::exists('wms_warehouses', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'code' => ['required', 'string', 'max:50'],
            'zone' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'metadata' => ['nullable', 'array'],
        ];
    }
}

