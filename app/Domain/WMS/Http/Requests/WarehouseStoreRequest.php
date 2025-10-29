<?php

namespace App\Domain\WMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WarehouseStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('wms_warehouses', 'code')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'address' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}

