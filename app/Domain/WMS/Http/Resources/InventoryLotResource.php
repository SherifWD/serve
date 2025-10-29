<?php

namespace App\Domain\WMS\Http\Resources;

use App\Domain\ERP\Http\Resources\ItemResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\WMS\Models\InventoryLot */
class InventoryLotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'item_id' => $this->item_id,
            'warehouse_id' => $this->warehouse_id,
            'storage_bin_id' => $this->storage_bin_id,
            'lot_number' => $this->lot_number,
            'quantity' => $this->quantity,
            'uom' => $this->uom,
            'received_at' => $this->received_at,
            'expires_at' => $this->expires_at,
            'metadata' => $this->metadata,
            'item' => new ItemResource($this->whenLoaded('item')),
            'warehouse' => WarehouseResource::make($this->whenLoaded('warehouse')),
            'storage_bin' => StorageBinResource::make($this->whenLoaded('storageBin')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

