<?php

namespace App\Domain\CRM\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\CRM\Models\Contact */
class ContactResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'account_id' => $this->account_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'position' => $this->position,
            'metadata' => $this->metadata,
            'account' => AccountResource::make($this->whenLoaded('account')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

