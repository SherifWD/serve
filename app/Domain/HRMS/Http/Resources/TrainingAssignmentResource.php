<?php

namespace App\Domain\HRMS\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\HRMS\Models\TrainingAssignment */
class TrainingAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'training_session_id' => $this->training_session_id,
            'worker_id' => $this->worker_id,
            'status' => $this->status,
            'worker' => WorkerResource::make($this->whenLoaded('worker')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

