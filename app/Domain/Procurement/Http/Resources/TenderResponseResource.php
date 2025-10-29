<?php

namespace App\Domain\Procurement\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TenderResponseResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'tender_id' => $this->tender_id,
            'vendor_id' => $this->vendor_id,
            'response_date' => $this->response_date?->toDateString(),
            'status' => $this->status,
            'amount' => $this->amount,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toDateTimeString(),
            'vendor' => VendorResource::make($this->whenLoaded('vendor')),
        ];
    }
}
