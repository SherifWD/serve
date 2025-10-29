<?php

namespace App\Domain\BI\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dashboard extends TenantModel
{
    protected $table = 'bi_dashboards';

    protected $fillable = [
        'tenant_id',
        'title',
        'description',
        'layout',
        'is_default',
    ];

    protected $casts = [
        'layout' => 'array',
        'is_default' => 'boolean',
    ];

    public function widgets(): HasMany
    {
        return $this->hasMany(DashboardWidget::class, 'dashboard_id');
    }
}

