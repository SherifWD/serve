<?php

namespace App\Domain\DMS\Models;

use App\Domain\Shared\Models\TenantModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentVersion extends TenantModel
{
    protected $table = 'dms_document_versions';

    protected $fillable = [
        'tenant_id',
        'document_id',
        'version_number',
        'file_path',
        'checksum',
        'uploaded_by',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
