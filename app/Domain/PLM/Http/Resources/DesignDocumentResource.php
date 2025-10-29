<?php

namespace App\Domain\PLM\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\PLM\Models\DesignDocument */
class DesignDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_design_id' => $this->product_design_id,
            'document_type' => $this->document_type,
            'file_name' => $this->file_name,
            'file_path' => $this->file_path,
            'version' => $this->version,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
        ];
    }
}

