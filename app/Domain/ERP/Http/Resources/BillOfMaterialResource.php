<?php

namespace App\Domain\ERP\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\ERP\Models\BillOfMaterial */
class BillOfMaterialResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'item_id' => $this->item_id,
            'code' => $this->code,
            'revision' => $this->revision,
            'status' => $this->status,
            'effective_from' => $this->effective_from,
            'effective_to' => $this->effective_to,
            'metadata' => $this->metadata,
            'item' => new ItemResource($this->whenLoaded('item')),
            'components' => BillOfMaterialLineResource::collection($this->whenLoaded('lines')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

