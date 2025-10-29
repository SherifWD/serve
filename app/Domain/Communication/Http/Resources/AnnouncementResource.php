<?php

namespace App\Domain\Communication\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AnnouncementResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'priority' => $this->priority,
            'status' => $this->status,
            'publish_at' => $this->publish_at?->toDateTimeString(),
            'expires_at' => $this->expires_at?->toDateTimeString(),
            'audiences' => $this->audiences,
            'attachments' => $this->attachments,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
