<?php

namespace App\Domain\MES\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\MES\Models\ProductionEvent */
class ProductionEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'work_order_id' => $this->work_order_id,
            'machine_id' => $this->machine_id,
            'event_type' => $this->event_type,
            'event_timestamp' => $this->event_timestamp,
            'payload' => $this->payload,
            'created_at' => $this->created_at,
        ];
    }
}

