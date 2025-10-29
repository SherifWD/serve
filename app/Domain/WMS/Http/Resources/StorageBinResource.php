<?php

namespace App\Domain\WMS\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\WMS\Models\StorageBin */
class StorageBinResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'warehouse_id' => $this->warehouse_id,
            'code' => $this->code,
            'zone' => $this->zone,
            'status' => $this->status,
            'metadata' => $this->metadata,
            'warehouse' => $this->whenLoaded('warehouse', fn () => [
                'id' => $this->warehouse?->id,
                'code' => $this->warehouse?->code,
                'name' => $this->warehouse?->name,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

