<?php

namespace App\Domain\BI\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DataSnapshotUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $snapshot = $this->route('data_snapshot');
        $snapshotId = $snapshot?->id ?? null;
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $currentKpiId = $snapshot?->kpi_id;

        return [
            'kpi_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('bi_kpis', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'snapshot_date' => [
                'sometimes',
                'required',
                'date',
                Rule::unique('bi_data_snapshots', 'snapshot_date')
                    ->ignore($snapshotId)
                    ->where(function ($query) use ($tenantId, $currentKpiId) {
                        $kpiId = $this->input('kpi_id', $currentKpiId);

                        return $query->where('tenant_id', $tenantId)->where('kpi_id', $kpiId);
                    }),
            ],
            'value' => ['sometimes', 'nullable', 'numeric'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
