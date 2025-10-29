<?php

namespace App\Domain\SCM\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\SCM\Models\PurchaseOrder */
class PurchaseOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'supplier_id' => $this->supplier_id,
            'po_number' => $this->po_number,
            'status' => $this->status,
            'order_date' => $this->order_date,
            'expected_date' => $this->expected_date,
            'subtotal' => $this->subtotal,
            'tax_total' => $this->tax_total,
            'grand_total' => $this->grand_total,
            'metadata' => $this->metadata,
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'lines' => PurchaseOrderLineResource::collection($this->whenLoaded('lines')),
            'shipments' => InboundShipmentResource::collection($this->whenLoaded('shipments')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

