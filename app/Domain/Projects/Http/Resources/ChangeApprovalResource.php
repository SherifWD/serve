<?php

namespace App\Domain\Projects\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ChangeApprovalResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'change_request_id' => $this->change_request_id,
            'approver_id' => $this->approver_id,
            'status' => $this->status,
            'role' => $this->role,
            'comments' => $this->comments,
            'acted_at' => $this->acted_at?->toDateTimeString(),
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toDateTimeString(),
            'approver' => $this->whenLoaded('approver', fn () => [
                'id' => $this->approver?->id,
                'name' => $this->approver?->name,
                'email' => $this->approver?->email,
            ]),
        ];
    }
}
