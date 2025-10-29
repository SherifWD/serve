<?php

namespace App\Domain\ERP\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\ERP\Models\Department */
class DepartmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'site_id' => $this->site_id,
            'code' => $this->code,
            'name' => $this->name,
            'status' => $this->status,
            'metadata' => $this->metadata,
            'site' => new SiteResource($this->whenLoaded('site')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

