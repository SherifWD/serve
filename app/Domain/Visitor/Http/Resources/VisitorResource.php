<?php

namespace App\Domain\Visitor\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VisitorResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'company' => $this->company,
            'email' => $this->email,
            'phone' => $this->phone,
            'id_type' => $this->id_type,
            'id_number' => $this->id_number,
            'status' => $this->status,
            'is_watchlisted' => (bool) $this->is_watchlisted,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'entries' => VisitorEntryResource::collection($this->whenLoaded('entries')),
        ];
    }
}
