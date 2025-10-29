<?php

namespace App\Domain\IoT\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DeviceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'device_key' => $this->device_key,
            'name' => $this->name,
            'device_type' => $this->device_type,
            'status' => $this->status,
            'location' => $this->location,
            'metadata' => $this->metadata,
            'installed_at' => $this->installed_at?->toDateString(),
            'last_heartbeat_at' => $this->last_heartbeat_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'sensors' => SensorResource::collection($this->whenLoaded('sensors')),
        ];
    }
}
