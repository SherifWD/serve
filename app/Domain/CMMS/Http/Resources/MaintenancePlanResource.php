<?php

namespace App\Domain\CMMS\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\CMMS\Models\MaintenancePlan */
class MaintenancePlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'asset_id' => $this->asset_id,
            'name' => $this->name,
            'frequency' => $this->frequency,
            'interval_days' => $this->interval_days,
            'tasks' => $this->tasks,
            'status' => $this->status,
            'asset' => AssetResource::make($this->whenLoaded('asset')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

