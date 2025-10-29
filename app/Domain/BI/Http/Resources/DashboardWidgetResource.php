<?php

namespace App\Domain\BI\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DashboardWidgetResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'dashboard_id' => $this->dashboard_id,
            'kpi_id' => $this->kpi_id,
            'type' => $this->type,
            'options' => $this->options,
            'position' => $this->position,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'kpi' => KpiResource::make($this->whenLoaded('kpi')),
        ];
    }
}
