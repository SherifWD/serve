<?php

namespace App\Domain\MES\Http\Resources;

use App\Domain\ERP\Http\Resources\ItemResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\MES\Models\WorkOrder */
class WorkOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'item_id' => $this->item_id,
            'production_line_id' => $this->production_line_id,
            'status' => $this->status,
            'quantity' => $this->quantity,
            'quantity_completed' => $this->quantity_completed,
            'planned_start_at' => $this->planned_start_at,
            'planned_end_at' => $this->planned_end_at,
            'actual_start_at' => $this->actual_start_at,
            'actual_end_at' => $this->actual_end_at,
            'metadata' => $this->metadata,
            'item' => new ItemResource($this->whenLoaded('item')),
            'production_line' => new ProductionLineResource($this->whenLoaded('productionLine')),
            'events' => ProductionEventResource::collection($this->whenLoaded('events')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

