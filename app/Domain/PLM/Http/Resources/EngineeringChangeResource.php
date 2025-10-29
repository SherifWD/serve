<?php

namespace App\Domain\PLM\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\PLM\Models\EngineeringChange */
class EngineeringChangeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_design_id' => $this->product_design_id,
            'code' => $this->code,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'effectivity_date' => $this->effectivity_date,
            'requested_by' => $this->requested_by,
            'approved_by' => $this->approved_by,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

