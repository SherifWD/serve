<?php

namespace App\Domain\BI\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardWidget extends TenantModel
{
    protected $table = 'bi_dashboard_widgets';

    protected $fillable = [
        'tenant_id',
        'dashboard_id',
        'kpi_id',
        'type',
        'options',
        'position',
    ];

    protected $casts = [
        'options' => 'array',
    ];

    public function dashboard(): BelongsTo
    {
        return $this->belongsTo(Dashboard::class);
    }

    public function kpi(): BelongsTo
    {
        return $this->belongsTo(Kpi::class, 'kpi_id');
    }
}

