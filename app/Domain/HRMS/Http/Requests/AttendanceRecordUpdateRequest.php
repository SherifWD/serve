<?php

namespace App\Domain\HRMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttendanceRecordUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        return [
            'worker_id' => [
                'sometimes',
                'required',
                Rule::exists('hrms_workers', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'attendance_date' => ['sometimes', 'required', 'date'],
            'check_in_at' => ['sometimes', 'nullable', 'date'],
            'check_out_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:check_in_at'],
            'status' => ['sometimes', 'required', Rule::in(['present', 'absent', 'remote', 'leave'])],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}

