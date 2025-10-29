<?php

namespace App\Domain\SCM\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\SCM\Models\PurchaseOrderLine */
class PurchaseOrderLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'item_id' => $this->item_id,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'uom' => $this->uom,
            'unit_price' => $this->unit_price,
            'line_total' => $this->line_total,
            'item' => $this->whenLoaded('item', fn () => [
                'id' => $this->item?->id,
                'code' => $this->item?->code,
                'name' => $this->item?->name,
            ]),
        ];
    }
}

