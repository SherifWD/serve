<?php

namespace App\Domain\CRM\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\CRM\Models\Account */
class AccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'industry' => $this->industry,
            'status' => $this->status,
            'address' => $this->address,
            'metadata' => $this->metadata,
            'contacts' => ContactResource::collection($this->whenLoaded('contacts')),
            'opportunities' => OpportunityResource::collection($this->whenLoaded('opportunities')),
            'service_cases' => ServiceCaseResource::collection($this->whenLoaded('serviceCases')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

