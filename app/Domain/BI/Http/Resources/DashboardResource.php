<?php

namespace App\Domain\BI\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'layout' => $this->layout,
            'is_default' => (bool) $this->is_default,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'widgets' => DashboardWidgetResource::collection($this->whenLoaded('widgets')),
        ];
    }
}
