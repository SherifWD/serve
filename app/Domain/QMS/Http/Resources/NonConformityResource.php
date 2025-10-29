<?php

namespace App\Domain\QMS\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\QMS\Models\NonConformity */
class NonConformityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'inspection_id' => $this->inspection_id,
            'code' => $this->code,
            'severity' => $this->severity,
            'status' => $this->status,
            'description' => $this->description,
            'metadata' => $this->metadata,
            'capa_actions' => CapaActionResource::collection($this->whenLoaded('capaActions')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

