<?php

namespace App\Domain\DMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DocumentStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        return [
            'folder_id' => [
                'nullable',
                'integer',
                Rule::exists('dms_document_folders', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'reference' => [
                'nullable',
                'string',
                'max:80',
                Rule::unique('dms_documents', 'reference')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'title' => ['required', 'string', 'max:255'],
            'document_type' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['draft', 'in_review', 'approved', 'archived'])],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:60'],
            'description' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
