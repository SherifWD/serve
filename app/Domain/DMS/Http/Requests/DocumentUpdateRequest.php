<?php

namespace App\Domain\DMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DocumentUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $document = $this->route('document');

        return [
            'folder_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('dms_document_folders', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'reference' => [
                'sometimes',
                'nullable',
                'string',
                'max:80',
                Rule::unique('dms_documents', 'reference')
                    ->ignore($document?->id ?? null)
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'document_type' => ['sometimes', 'nullable', 'string', 'max:120'],
            'status' => ['sometimes', 'required', Rule::in(['draft', 'in_review', 'approved', 'archived'])],
            'tags' => ['sometimes', 'nullable', 'array'],
            'tags.*' => ['string', 'max:60'],
            'description' => ['sometimes', 'nullable', 'string'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
