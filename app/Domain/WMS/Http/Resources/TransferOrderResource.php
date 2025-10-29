<?php

namespace App\Domain\WMS\Http\Resources;

use App\Domain\ERP\Http\Resources\ItemResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\WMS\Models\TransferOrder */
class TransferOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'source_bin_id' => $this->source_bin_id,
            'destination_bin_id' => $this->destination_bin_id,
            'item_id' => $this->item_id,
            'quantity' => $this->quantity,
            'status' => $this->status,
            'requested_at' => $this->requested_at,
            'completed_at' => $this->completed_at,
            'metadata' => $this->metadata,
            'source_bin' => new StorageBinResource($this->whenLoaded('sourceBin')),
            'destination_bin' => new StorageBinResource($this->whenLoaded('destinationBin')),
            'item' => new ItemResource($this->whenLoaded('item')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

