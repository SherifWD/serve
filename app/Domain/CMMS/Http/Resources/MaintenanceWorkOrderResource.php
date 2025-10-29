<?php

namespace App\Domain\CMMS\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\CMMS\Models\MaintenanceWorkOrder */
class MaintenanceWorkOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'asset_id' => $this->asset_id,
            'maintenance_plan_id' => $this->maintenance_plan_id,
            'status' => $this->status,
            'priority' => $this->priority,
            'description' => $this->description,
            'scheduled_date' => $this->scheduled_date,
            'completed_date' => $this->completed_date,
            'metadata' => $this->metadata,
            'asset' => AssetResource::make($this->whenLoaded('asset')),
            'plan' => MaintenancePlanResource::make($this->whenLoaded('plan')),
            'logs' => MaintenanceLogResource::collection($this->whenLoaded('logs')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

