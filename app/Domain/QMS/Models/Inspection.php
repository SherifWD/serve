<?php

namespace App\Domain\QMS\Models;

use App\Domain\ERP\Models\Item;
use App\Domain\Shared\Models\TenantModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inspection extends TenantModel
{
    protected $table = 'qms_inspections';

    protected $fillable = [
        'tenant_id',
        'inspection_plan_id',
        'item_id',
        'reference',
        'status',
        'results',
        'inspected_at',
        'inspected_by',
    ];

    protected $casts = [
        'results' => 'array',
        'inspected_at' => 'datetime',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(InspectionPlan::class, 'inspection_plan_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    public function nonConformities(): HasMany
    {
        return $this->hasMany(NonConformity::class, 'inspection_id');
    }
}

