<?php

namespace App\Domain\CMMS\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\CMMS\Models\Asset */
class AssetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'asset_type' => $this->asset_type,
            'status' => $this->status,
            'location' => $this->location,
            'metadata' => $this->metadata,
            'commissioned_at' => $this->commissioned_at,
            'maintenance_plans' => MaintenancePlanResource::collection($this->whenLoaded('maintenancePlans')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

