<?php

namespace App\Domain\WMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorageBinUpdateRequest extends FormRequest
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
                'sometimes',
                'required',
                Rule::exists('wms_warehouses', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'code' => ['sometimes', 'required', 'string', 'max:50'],
            'zone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'status' => ['sometimes', 'required', Rule::in(['active', 'inactive'])],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}

