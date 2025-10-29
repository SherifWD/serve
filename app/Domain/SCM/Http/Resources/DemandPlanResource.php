<?php

namespace App\Domain\SCM\Http\Resources;

use App\Domain\ERP\Http\Resources\ItemResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\SCM\Models\DemandPlan */
class DemandPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'item_id' => $this->item_id,
            'period' => $this->period,
            'forecast_quantity' => $this->forecast_quantity,
            'planning_strategy' => $this->planning_strategy,
            'assumptions' => $this->assumptions,
            'item' => new ItemResource($this->whenLoaded('item')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

