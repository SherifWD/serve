<?php

namespace App\Domain\ERP\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\ERP\Models\Item */
class ItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'sku' => $this->sku,
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'uom' => $this->uom,
            'status' => $this->status,
            'standard_cost' => $this->standard_cost,
            'list_price' => $this->list_price,
            'attributes' => $this->attributes,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'code' => $this->category->code,
                'name' => $this->category->name,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

