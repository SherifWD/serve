<?php

namespace App\Domain\QMS\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\QMS\Models\Audit */
class AuditResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'audit_type' => $this->audit_type,
            'scheduled_date' => $this->scheduled_date,
            'completed_date' => $this->completed_date,
            'status' => $this->status,
            'findings' => $this->findings,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

