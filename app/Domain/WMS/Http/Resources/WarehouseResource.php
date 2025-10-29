<?php

namespace App\Domain\WMS\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\WMS\Models\Warehouse */
class WarehouseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'status' => $this->status,
            'address' => $this->address,
            'metadata' => $this->metadata,
            'storage_bins' => StorageBinResource::collection($this->whenLoaded('storageBins')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

