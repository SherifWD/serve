<?php

namespace App\Domain\BI\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataSnapshot extends TenantModel
{
    protected $table = 'bi_data_snapshots';

    protected $fillable = [
        'tenant_id',
        'kpi_id',
        'snapshot_date',
        'value',
        'metadata',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'value' => 'decimal:4',
        'metadata' => 'array',
    ];

    public function kpi(): BelongsTo
    {
        return $this->belongsTo(Kpi::class);
    }
}

