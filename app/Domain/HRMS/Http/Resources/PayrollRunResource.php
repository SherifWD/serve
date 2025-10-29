<?php

namespace App\Domain\HRMS\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\HRMS\Models\PayrollRun */
class PayrollRunResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'period' => $this->period,
            'status' => $this->status,
            'pay_date' => $this->pay_date,
            'gross_total' => $this->gross_total,
            'net_total' => $this->net_total,
            'entries' => PayrollEntryResource::collection($this->whenLoaded('entries')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

