<?php

namespace App\Domain\CMMS\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\CMMS\Models\SparePart */
class SparePartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'uom' => $this->uom,
            'quantity_on_hand' => $this->quantity_on_hand,
            'reorder_level' => $this->reorder_level,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

