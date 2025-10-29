<?php

namespace App\Domain\Projects\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProjectTaskResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'title' => $this->title,
            'description' => $this->description,
            'assignee_id' => $this->assignee_id,
            'status' => $this->status,
            'priority' => $this->priority,
            'start_date' => $this->start_date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'completed_at' => $this->completed_at?->toDateTimeString(),
            'progress' => $this->progress,
            'depends_on_task_id' => $this->depends_on_task_id,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toDateTimeString(),
            'assignee' => $this->whenLoaded('assignee', fn () => [
                'id' => $this->assignee?->id,
                'name' => $this->assignee?->name,
                'email' => $this->assignee?->email,
            ]),
            'dependency' => $this->whenLoaded('dependency', fn () => [
                'id' => $this->dependency?->id,
                'title' => $this->dependency?->title,
            ]),
        ];
    }
}
