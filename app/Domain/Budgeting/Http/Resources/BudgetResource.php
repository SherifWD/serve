<?php

namespace App\Domain\Budgeting\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BudgetResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'cost_center_id' => $this->cost_center_id,
            'fiscal_year' => $this->fiscal_year,
            'period' => $this->period,
            'status' => $this->status,
            'planned_amount' => $this->planned_amount,
            'approved_amount' => $this->approved_amount,
            'forecast_amount' => $this->forecast_amount,
            'currency' => $this->currency,
            'assumptions' => $this->assumptions,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'cost_center' => CostCenterResource::make($this->whenLoaded('costCenter')),
        ];
    }
}
