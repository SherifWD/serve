<?php

namespace App\Domain\QMS\Http\Resources;

use App\Domain\ERP\Http\Resources\ItemResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\QMS\Models\Inspection */
class InspectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'inspection_plan_id' => $this->inspection_plan_id,
            'item_id' => $this->item_id,
            'reference' => $this->reference,
            'status' => $this->status,
            'results' => $this->results,
            'inspected_at' => $this->inspected_at,
            'inspected_by' => $this->inspected_by,
            'plan' => new InspectionPlanResource($this->whenLoaded('plan')),
            'item' => new ItemResource($this->whenLoaded('item')),
            'inspector' => $this->whenLoaded('inspector', fn () => [
                'id' => $this->inspector?->id,
                'name' => $this->inspector?->name,
            ]),
            'non_conformities' => NonConformityResource::collection($this->whenLoaded('nonConformities')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

