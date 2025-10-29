<?php

namespace App\Domain\Communication\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WorkflowActionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'workflow_request_id' => $this->workflow_request_id,
            'actor_id' => $this->actor_id,
            'action' => $this->action,
            'status' => $this->status,
            'comments' => $this->comments,
            'acted_at' => $this->acted_at?->toDateTimeString(),
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toDateTimeString(),
            'actor' => $this->whenLoaded('actor', fn () => [
                'id' => $this->actor?->id,
                'name' => $this->actor?->name,
                'email' => $this->actor?->email,
            ]),
        ];
    }
}
