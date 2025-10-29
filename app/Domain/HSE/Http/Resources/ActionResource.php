<?php

namespace App\Domain\HSE\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ActionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'incident_id' => $this->incident_id,
            'audit_id' => $this->audit_id,
            'action_type' => $this->action_type,
            'description' => $this->description,
            'status' => $this->status,
            'due_date' => $this->due_date?->toDateString(),
            'completed_date' => $this->completed_date?->toDateString(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
