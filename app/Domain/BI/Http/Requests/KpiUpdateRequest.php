<?php

namespace App\Domain\BI\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KpiUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $kpi = $this->route('kpi');
        $kpiId = $kpi?->id ?? null;
        $tenantId = app('tenant.context')->ensureTenant()->id;

        return [
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:60',
                Rule::unique('bi_kpis', 'code')
                    ->ignore($kpiId)
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'category' => ['sometimes', 'nullable', 'string', 'max:120'],
            'unit' => ['sometimes', 'nullable', 'string', 'max:40'],
            'config' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
