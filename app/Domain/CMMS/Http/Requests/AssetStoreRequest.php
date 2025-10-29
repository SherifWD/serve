<?php

namespace App\Domain\CMMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssetStoreRequest extends FormRequest
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
                Rule::unique('cmms_assets', 'code')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'asset_type' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'retired'])],
            'location' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
            'commissioned_at' => ['nullable', 'date'],
        ];
    }
}

