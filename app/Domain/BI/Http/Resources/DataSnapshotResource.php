<?php

namespace App\Domain\BI\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DataSnapshotResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'kpi_id' => $this->kpi_id,
            'snapshot_date' => $this->snapshot_date?->toDateString(),
            'value' => $this->value !== null ? (float) $this->value : null,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'kpi' => KpiResource::make($this->whenLoaded('kpi')),
        ];
    }
}
