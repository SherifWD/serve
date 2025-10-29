<?php

namespace App\Domain\HRMS\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\HRMS\Models\PayrollEntry */
class PayrollEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'worker_id' => $this->worker_id,
            'gross_amount' => $this->gross_amount,
            'net_amount' => $this->net_amount,
            'breakdown' => $this->breakdown,
            'worker' => WorkerResource::make($this->whenLoaded('worker')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

