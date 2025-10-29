<?php

namespace App\Domain\Projects\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'status' => $this->status,
            'stage' => $this->stage,
            'description' => $this->description,
            'start_date' => $this->start_date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'owner_id' => $this->owner_id,
            'budget_amount' => $this->budget_amount,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'owner' => $this->whenLoaded('owner', fn () => [
                'id' => $this->owner?->id,
                'name' => $this->owner?->name,
                'email' => $this->owner?->email,
            ]),
            'tasks' => ProjectTaskResource::collection($this->whenLoaded('tasks')),
            'change_requests' => ChangeRequestResource::collection($this->whenLoaded('changeRequests')),
        ];
    }
}
