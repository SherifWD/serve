<?php

namespace App\Domain\MES\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductionEventStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        return [
            'work_order_id' => [
                'required',
                Rule::exists('mes_work_orders', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'machine_id' => [
                'nullable',
                Rule::exists('mes_machines', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'event_type' => ['required', 'string', 'max:100'],
            'event_timestamp' => ['required', 'date'],
            'payload' => ['nullable', 'array'],
        ];
    }
}

