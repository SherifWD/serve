<?php

namespace App\Domain\QMS\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\QMS\Models\CapaAction */
class CapaActionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'non_conformity_id' => $this->non_conformity_id,
            'action_type' => $this->action_type,
            'description' => $this->description,
            'status' => $this->status,
            'due_at' => $this->due_at,
            'completed_at' => $this->completed_at,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

