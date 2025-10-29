<?php

namespace App\Domain\DMS\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'folder_id' => $this->folder_id,
            'reference' => $this->reference,
            'title' => $this->title,
            'document_type' => $this->document_type,
            'status' => $this->status,
            'latest_version_number' => $this->latest_version_number,
            'tags' => $this->tags,
            'description' => $this->description,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'folder' => DocumentFolderResource::make($this->whenLoaded('folder')),
            'versions' => DocumentVersionResource::collection($this->whenLoaded('versions')),
        ];
    }
}
