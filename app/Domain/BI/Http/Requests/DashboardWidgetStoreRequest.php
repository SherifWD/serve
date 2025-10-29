<?php

namespace App\Domain\BI\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DashboardWidgetStoreRequest extends FormRequest
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
                'required',
                'integer',
                Rule::exists('bi_dashboards', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'kpi_id' => [
                'nullable',
                'integer',
                Rule::exists('bi_kpis', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'type' => ['nullable', Rule::in(['metric', 'chart', 'table'])],
            'options' => ['nullable', 'array'],
            'position' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
