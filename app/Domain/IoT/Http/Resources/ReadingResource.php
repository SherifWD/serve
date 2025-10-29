<?php

namespace App\Domain\IoT\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReadingResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'sensor_id' => $this->sensor_id,
            'recorded_at' => $this->recorded_at?->toDateTimeString(),
            'value' => $this->value,
            'quality' => $this->quality,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toDateTimeString(),
            'sensor' => SensorResource::make($this->whenLoaded('sensor')),
        ];
    }
}
