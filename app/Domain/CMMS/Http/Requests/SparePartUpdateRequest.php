<?php

namespace App\Domain\CMMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SparePartUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $partId = $this->route('spare_part')?->id ?? null;

        return [
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('cmms_spare_parts', 'code')
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId))
                    ->ignore($partId),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'uom' => ['sometimes', 'nullable', 'string', 'max:20'],
            'quantity_on_hand' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'reorder_level' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}

