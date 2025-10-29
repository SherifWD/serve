<?php

namespace App\Domain\DMS\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DocumentFolderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'children' => DocumentFolderResource::collection($this->whenLoaded('children')),
        ];
    }
}
