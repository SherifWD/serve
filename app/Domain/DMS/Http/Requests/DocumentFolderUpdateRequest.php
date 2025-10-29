<?php

namespace App\Domain\DMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DocumentFolderUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $folder = $this->route('document_folder');
        $folderId = $folder?->id ?? null;

        return [
            'parent_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('dms_document_folders', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => [
                'sometimes',
                'nullable',
                'string',
                'max:60',
                Rule::unique('dms_document_folders', 'code')
                    ->ignore($folderId)
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'description' => ['sometimes', 'nullable', 'string'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
