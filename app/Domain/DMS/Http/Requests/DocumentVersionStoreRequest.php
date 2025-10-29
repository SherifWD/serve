<?php

namespace App\Domain\DMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DocumentVersionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        return [
            'document_id' => [
                'required',
                'integer',
                Rule::exists('dms_documents', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'file_path' => ['required', 'string', 'max:255'],
            'version_number' => ['nullable', 'integer', 'min:1'],
            'checksum' => ['nullable', 'string', 'max:120'],
            'uploaded_by' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
