<?php

namespace App\Domain\HRMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TrainingAssignmentStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        return [
            'training_session_id' => [
                'required',
                Rule::exists('hrms_training_sessions', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'worker_id' => [
                'required',
                Rule::exists('hrms_workers', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'status' => ['nullable', Rule::in(['assigned', 'attended', 'completed', 'missed'])],
        ];
    }
}

