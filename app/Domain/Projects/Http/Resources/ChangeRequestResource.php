<?php

namespace App\Domain\Projects\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ChangeRequestResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'reference' => $this->reference,
            'title' => $this->title,
            'change_type' => $this->change_type,
            'status' => $this->status,
            'requested_by' => $this->requested_by,
            'requested_at' => $this->requested_at?->toDateString(),
            'target_date' => $this->target_date?->toDateString(),
            'risk_level' => $this->risk_level,
            'impact_summary' => $this->impact_summary,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toDateTimeString(),
            'project' => ProjectResource::make($this->whenLoaded('project')),
            'requester' => $this->whenLoaded('requester', fn () => [
                'id' => $this->requester?->id,
                'name' => $this->requester?->name,
                'email' => $this->requester?->email,
            ]),
            'approvals' => ChangeApprovalResource::collection($this->whenLoaded('approvals')),
        ];
    }
}
