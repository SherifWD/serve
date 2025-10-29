<?php

namespace App\Domain\ERP\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SiteUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $siteId = $this->route('site')?->id ?? null;

        return [
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('erp_sites', 'code')
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId))
                    ->ignore($siteId),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'status' => ['sometimes', 'required', 'in:active,inactive'],
            'timezone' => ['sometimes', 'required', 'string', 'max:100'],
            'address' => ['sometimes', 'nullable', 'array'],
            'settings' => ['sometimes', 'nullable', 'array'],
        ];
    }
}

