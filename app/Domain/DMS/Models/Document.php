<?php

namespace App\Domain\DMS\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends TenantModel
{
    protected $table = 'dms_documents';

    protected $fillable = [
        'tenant_id',
        'folder_id',
        'reference',
        'title',
        'document_type',
        'status',
        'latest_version_number',
        'tags',
        'description',
        'metadata',
    ];

    protected $casts = [
        'tags' => 'array',
        'metadata' => 'array',
    ];

    public function folder(): BelongsTo
    {
        return $this->belongsTo(DocumentFolder::class, 'folder_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class, 'document_id')->orderByDesc('version_number');
    }
}
