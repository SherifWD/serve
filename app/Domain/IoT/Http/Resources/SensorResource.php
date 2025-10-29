<?php

namespace App\Domain\IoT\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SensorResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'device_id' => $this->device_id,
            'tag' => $this->tag,
            'name' => $this->name,
            'unit' => $this->unit,
            'data_type' => $this->data_type,
            'threshold_min' => $this->threshold_min,
            'threshold_max' => $this->threshold_max,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toDateTimeString(),
            'device' => DeviceResource::make($this->whenLoaded('device')),
            'readings' => ReadingResource::collection($this->whenLoaded('readings')),
        ];
    }
}
