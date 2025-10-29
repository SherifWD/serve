<?php

namespace App\Domain\ERP\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\ERP\Models\BillOfMaterialLine */
class BillOfMaterialLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'component_item_id' => $this->component_item_id,
            'quantity' => $this->quantity,
            'uom' => $this->uom,
            'sequence' => $this->sequence,
            'metadata' => $this->metadata,
            'component' => new ItemResource($this->whenLoaded('component')),
        ];
    }
}

