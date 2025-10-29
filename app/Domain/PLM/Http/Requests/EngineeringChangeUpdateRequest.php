<?php

namespace App\Domain\PLM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EngineeringChangeUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $changeId = $this->route('engineering_change')?->id ?? null;

        return [
            'product_design_id' => [
                'sometimes',
                'required',
                Rule::exists('plm_product_designs', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('plm_engineering_changes', 'code')
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId))
                    ->ignore($changeId),
            ],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'required', Rule::in(['draft', 'submitted', 'approved', 'implemented', 'rejected'])],
            'effectivity_date' => ['sometimes', 'nullable', 'date'],
            'requested_by' => [
                'sometimes',
                'nullable',
                Rule::exists('users', 'id'),
            ],
            'approved_by' => [
                'sometimes',
                'nullable',
                Rule::exists('users', 'id'),
            ],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
