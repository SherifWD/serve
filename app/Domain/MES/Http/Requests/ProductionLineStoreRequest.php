<?php

namespace App\Domain\MES\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductionLineStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        return [
            'site_id' => [
                'nullable',
                Rule::exists('erp_sites', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('mes_production_lines', 'code')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'layout' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}

