<?php

namespace App\Domain\PLM\Http\Resources;

use App\Domain\ERP\Http\Resources\ItemResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\PLM\Models\ProductDesign */
class ProductDesignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'item_id' => $this->item_id,
            'code' => $this->code,
            'name' => $this->name,
            'version' => $this->version,
            'lifecycle_state' => $this->lifecycle_state,
            'description' => $this->description,
            'metadata' => $this->metadata,
            'item' => new ItemResource($this->whenLoaded('item')),
            'changes' => EngineeringChangeResource::collection($this->whenLoaded('changes')),
            'documents' => DesignDocumentResource::collection($this->whenLoaded('documents')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

