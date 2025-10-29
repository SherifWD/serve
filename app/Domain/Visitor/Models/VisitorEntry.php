<?php

namespace App\Domain\Visitor\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitorEntry extends TenantModel
{
    protected $table = 'visitor_entries';

    protected $fillable = [
        'tenant_id',
        'visitor_id',
        'host_name',
        'host_department',
        'purpose',
        'scheduled_start',
        'scheduled_end',
        'check_in_at',
        'check_out_at',
        'badge_number',
        'status',
        'metadata',
    ];

    protected $casts = [
        'scheduled_start' => 'datetime',
        'scheduled_end' => 'datetime',
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class, 'visitor_id');
    }
}
