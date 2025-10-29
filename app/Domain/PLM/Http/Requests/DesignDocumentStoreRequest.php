<?php

namespace App\Domain\PLM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DesignDocumentStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        return [
            'product_design_id' => [
                'required',
                Rule::exists('plm_product_designs', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'document_type' => ['nullable', 'string', 'max:100'],
            'file_name' => ['required', 'string', 'max:255'],
            'file_path' => ['required', 'string', 'max:500'],
            'version' => ['nullable', 'string', 'max:50'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}

