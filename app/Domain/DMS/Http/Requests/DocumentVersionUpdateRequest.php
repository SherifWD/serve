<?php

namespace App\Domain\DMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DocumentVersionUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $version = $this->route('document_version');
        $documentId = $version?->document_id ?? null;

        return [
            'file_path' => ['sometimes', 'required', 'string', 'max:255'],
            'version_number' => [
                'sometimes',
                'nullable',
                'integer',
                'min:1',
                Rule::unique('dms_document_versions', 'version_number')
                    ->where(fn ($q) => $documentId ? $q->where('document_id', $documentId) : $q)
                    ->ignore($version?->id ?? null),
            ],
            'checksum' => ['sometimes', 'nullable', 'string', 'max:120'],
            'uploaded_by' => ['sometimes', 'nullable', 'integer', Rule::exists('users', 'id')],
            'notes' => ['sometimes', 'nullable', 'string'],
            'metadata' => ['sometimes', 'nullable', 'array'],
            'document_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('dms_documents', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
        ];
    }
}
