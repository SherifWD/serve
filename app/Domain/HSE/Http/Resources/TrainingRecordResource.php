<?php

namespace App\Domain\HSE\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TrainingRecordResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'session_date' => $this->session_date?->toDateString(),
            'trainer' => $this->trainer,
            'attendees' => $this->attendees,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
