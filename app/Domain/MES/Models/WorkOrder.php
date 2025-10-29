<?php

namespace App\Domain\MES\Models;

use App\Domain\ERP\Models\Item;
use App\Domain\Shared\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkOrder extends TenantModel
{
    protected $table = 'mes_work_orders';

    protected $fillable = [
        'tenant_id',
        'item_id',
        'production_line_id',
        'code',
        'status',
        'quantity',
        'quantity_completed',
        'planned_start_at',
        'planned_end_at',
        'actual_start_at',
        'actual_end_at',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'quantity_completed' => 'decimal:4',
        'planned_start_at' => 'datetime',
        'planned_end_at' => 'datetime',
        'actual_start_at' => 'datetime',
        'actual_end_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function productionLine(): BelongsTo
    {
        return $this->belongsTo(ProductionLine::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ProductionEvent::class);
    }
}
