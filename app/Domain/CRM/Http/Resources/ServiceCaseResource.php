<?php

namespace App\Domain\CRM\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\CRM\Models\ServiceCase */
class ServiceCaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'account_id' => $this->account_id,
            'case_number' => $this->case_number,
            'title' => $this->title,
            'status' => $this->status,
            'priority' => $this->priority,
            'description' => $this->description,
            'metadata' => $this->metadata,
            'account' => AccountResource::make($this->whenLoaded('account')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

