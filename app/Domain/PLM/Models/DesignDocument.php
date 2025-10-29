<?php

namespace App\Domain\PLM\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DesignDocument extends TenantModel
{
    protected $table = 'plm_design_documents';

    protected $fillable = [
        'tenant_id',
        'product_design_id',
        'document_type',
        'file_name',
        'file_path',
        'version',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function design(): BelongsTo
    {
        return $this->belongsTo(ProductDesign::class, 'product_design_id');
    }
}

