<?php

namespace App\Domain\HRMS\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\HRMS\Models\AttendanceRecord */
class AttendanceRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'worker_id' => $this->worker_id,
            'attendance_date' => $this->attendance_date,
            'check_in_at' => $this->check_in_at,
            'check_out_at' => $this->check_out_at,
            'status' => $this->status,
            'metadata' => $this->metadata,
            'worker' => WorkerResource::make($this->whenLoaded('worker')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

