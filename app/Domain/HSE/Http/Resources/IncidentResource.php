<?php

namespace App\Domain\HSE\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class IncidentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'title' => $this->title,
            'incident_date' => $this->incident_date?->toDateString(),
            'severity' => $this->severity,
            'status' => $this->status,
            'description' => $this->description,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'actions' => ActionResource::collection($this->whenLoaded('actions')),
        ];
    }
}
