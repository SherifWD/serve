<?php

namespace App\Domain\BI\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DashboardWidgetUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        return [
            'dashboard_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('bi_dashboards', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'kpi_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('bi_kpis', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'type' => ['sometimes', 'nullable', Rule::in(['metric', 'chart', 'table'])],
            'options' => ['sometimes', 'nullable', 'array'],
            'position' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ];
    }
}
