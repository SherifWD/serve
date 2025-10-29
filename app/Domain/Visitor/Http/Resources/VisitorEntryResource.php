<?php

namespace App\Domain\Visitor\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VisitorEntryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'visitor_id' => $this->visitor_id,
            'host_name' => $this->host_name,
            'host_department' => $this->host_department,
            'purpose' => $this->purpose,
            'scheduled_start' => $this->scheduled_start?->toDateTimeString(),
            'scheduled_end' => $this->scheduled_end?->toDateTimeString(),
            'check_in_at' => $this->check_in_at?->toDateTimeString(),
            'check_out_at' => $this->check_out_at?->toDateTimeString(),
            'badge_number' => $this->badge_number,
            'status' => $this->status,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toDateTimeString(),
            'visitor' => VisitorResource::make($this->whenLoaded('visitor')),
        ];
    }
}
