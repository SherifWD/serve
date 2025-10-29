<?php

namespace App\Domain\HRMS\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\HRMS\Models\TrainingSession */
class TrainingSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'scheduled_date' => $this->scheduled_date,
            'status' => $this->status,
            'metadata' => $this->metadata,
            'assignments' => TrainingAssignmentResource::collection($this->whenLoaded('assignments')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

