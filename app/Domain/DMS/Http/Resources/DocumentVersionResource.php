<?php

namespace App\Domain\DMS\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DocumentVersionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'document_id' => $this->document_id,
            'version_number' => $this->version_number,
            'file_path' => $this->file_path,
            'checksum' => $this->checksum,
            'uploaded_by' => $this->uploaded_by,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toDateTimeString(),
            'uploader' => $this->whenLoaded('uploader', fn () => [
                'id' => $this->uploader?->id,
                'name' => $this->uploader?->name,
                'email' => $this->uploader?->email,
            ]),
        ];
    }
}
