<?php

namespace App\Domain\HRMS\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\HRMS\Models\Worker */
class WorkerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_number' => $this->employee_number,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'employment_status' => $this->employment_status,
            'hire_date' => $this->hire_date,
            'metadata' => $this->metadata,
            'contracts' => EmploymentContractResource::collection($this->whenLoaded('contracts')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

