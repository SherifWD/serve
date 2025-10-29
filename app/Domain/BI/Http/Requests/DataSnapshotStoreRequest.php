<?php

namespace App\Domain\BI\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DataSnapshotStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        return [
            'kpi_id' => [
                'required',
                'integer',
                Rule::exists('bi_kpis', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'snapshot_date' => [
                'required',
                'date',
                Rule::unique('bi_data_snapshots', 'snapshot_date')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId)->where('kpi_id', $this->input('kpi_id'));
                }),
            ],
            'value' => ['nullable', 'numeric'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
