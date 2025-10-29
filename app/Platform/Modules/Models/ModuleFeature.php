<?php

namespace App\Platform\Modules\Models;

use App\Platform\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ModuleFeature extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_id',
        'key',
        'name',
        'category',
        'is_default',
        'description',
        'metadata',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'metadata' => 'array',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_module_features')
            ->withPivot(['status', 'settings'])
            ->withTimestamps();
    }
}

