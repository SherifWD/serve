<?php

namespace App\Domain\Communication\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WorkflowRequestResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'request_type' => $this->request_type,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'requester_id' => $this->requester_id,
            'assignee_id' => $this->assignee_id,
            'requested_at' => $this->requested_at?->toDateString(),
            'due_at' => $this->due_at?->toDateString(),
            'payload' => $this->payload,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'requester' => $this->whenLoaded('requester', fn () => [
                'id' => $this->requester?->id,
                'name' => $this->requester?->name,
                'email' => $this->requester?->email,
            ]),
            'assignee' => $this->whenLoaded('assignee', fn () => [
                'id' => $this->assignee?->id,
                'name' => $this->assignee?->name,
                'email' => $this->assignee?->email,
            ]),
            'actions' => WorkflowActionResource::collection($this->whenLoaded('actions')),
        ];
    }
}
