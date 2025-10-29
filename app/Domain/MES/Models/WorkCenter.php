<?php

namespace App\Domain\MES\Models;

use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkCenter extends TenantModel
{
    protected $table = 'mes_work_centers';

    protected $fillable = [
        'tenant_id',
        'production_line_id',
        'code',
        'name',
        'status',
        'metadata',
    ];

    public function productionLine(): BelongsTo
    {
        return $this->belongsTo(ProductionLine::class);
    }

    public function machines(): HasMany
    {
        return $this->hasMany(Machine::class);
    }
}

