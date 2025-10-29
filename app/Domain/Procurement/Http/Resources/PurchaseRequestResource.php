<?php

namespace App\Domain\Procurement\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseRequestResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'requester_name' => $this->requester_name,
            'department' => $this->department,
            'status' => $this->status,
            'needed_by' => $this->needed_by?->toDateString(),
            'total_amount' => $this->total_amount,
            'justification' => $this->justification,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
