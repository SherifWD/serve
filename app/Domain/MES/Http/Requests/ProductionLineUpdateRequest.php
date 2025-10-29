<?php

namespace App\Domain\MES\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductionLineUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $productionLineId = $this->route('production_line')?->id ?? null;

        return [
            'site_id' => [
                'sometimes',
                'nullable',
                Rule::exists('erp_sites', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('mes_production_lines', 'code')
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId))
                    ->ignore($productionLineId),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'status' => ['sometimes', 'required', Rule::in(['active', 'inactive'])],
            'layout' => ['sometimes', 'nullable', 'array'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
