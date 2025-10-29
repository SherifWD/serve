<?php

namespace App\Domain\Commerce\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'customer_id' => $this->customer_id,
            'channel' => $this->channel,
            'status' => $this->status,
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'shipping_fee' => $this->shipping_fee,
            'tax' => $this->tax,
            'total' => $this->total,
            'currency' => $this->currency,
            'placed_at' => $this->placed_at?->toDateTimeString(),
            'fulfilled_at' => $this->fulfilled_at?->toDateTimeString(),
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'customer' => CustomerResource::make($this->whenLoaded('customer')),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
