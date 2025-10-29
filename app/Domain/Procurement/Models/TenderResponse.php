<?php

namespace App\Domain\Procurement\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenderResponse extends TenantModel
{
    protected $table = 'procurement_tender_responses';

    protected $fillable = [
        'tenant_id',
        'tender_id',
        'vendor_id',
        'response_date',
        'status',
        'amount',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'response_date' => 'date',
        'amount' => 'float',
        'metadata' => 'array',
    ];

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class, 'tender_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }
}
