<?php

namespace App\Domain\CMMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssetUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $assetId = $this->route('asset')?->id ?? null;

        return [
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('cmms_assets', 'code')
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId))
                    ->ignore($assetId),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'asset_type' => ['sometimes', 'nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'required', Rule::in(['active', 'inactive', 'retired'])],
            'location' => ['sometimes', 'nullable', 'array'],
            'metadata' => ['sometimes', 'nullable', 'array'],
            'commissioned_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}

