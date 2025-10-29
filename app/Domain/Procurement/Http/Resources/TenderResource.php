<?php

namespace App\Domain\Procurement\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TenderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'title' => $this->title,
            'status' => $this->status,
            'opening_date' => $this->opening_date?->toDateString(),
            'closing_date' => $this->closing_date?->toDateString(),
            'description' => $this->description,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toDateTimeString(),
            'responses' => TenderResponseResource::collection($this->whenLoaded('responses')),
        ];
    }
}
