<?php

namespace App\Domain\Budgeting\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ActualResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'cost_center_id' => $this->cost_center_id,
            'fiscal_year' => $this->fiscal_year,
            'period' => $this->period,
            'actual_amount' => $this->actual_amount,
            'currency' => $this->currency,
            'source_reference' => $this->source_reference,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'cost_center' => CostCenterResource::make($this->whenLoaded('costCenter')),
        ];
    }
}
