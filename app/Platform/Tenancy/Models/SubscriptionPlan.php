<?php

namespace App\Platform\Tenancy\Models;

use App\Platform\Modules\Models\Module;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'billing_cycle',
        'price_cents',
        'currency',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'subscription_plan_module')
            ->withPivot(['seat_limit', 'is_optional'])
            ->withTimestamps();
    }

    public function price(string $format = 'float'): float|int|string
    {
        $amount = $this->price_cents / 100;

        return $format === 'formatted'
            ? number_format($amount, 2) . ' ' . $this->currency
            : ($format === 'int' ? (int) $this->price_cents : $amount);
    }
}

