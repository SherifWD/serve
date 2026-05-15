<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FiscalProfile extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_default' => 'boolean',
        'price_includes_vat' => 'boolean',
        'vat_rate' => 'decimal:4',
        'buyer_id_threshold' => 'decimal:2',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public static function effectiveForBranch(?int $branchId, ?int $restaurantId = null): self
    {
        $branch = $branchId ? Branch::query()->with('restaurant')->find($branchId) : null;
        $restaurantId = $restaurantId ?: $branch?->restaurant_id;

        $profile = null;

        if ($branchId) {
            $profile = static::query()->where('branch_id', $branchId)->latest('id')->first();
        }

        if (!$profile && $restaurantId) {
            $profile = static::query()
                ->where('restaurant_id', $restaurantId)
                ->whereNull('branch_id')
                ->orderByDesc('is_default')
                ->latest('id')
                ->first();
        }

        return $profile ?: new static([
            'restaurant_id' => $restaurantId,
            'branch_id' => $branchId,
            'display_name' => 'Generated default fiscal profile',
            'is_default' => true,
            'currency_code' => 'EGP',
            'vat_rate' => 0.14,
            'price_includes_vat' => true,
            'vat_tax_type' => 'T1',
            'vat_subtype' => 'V009',
            'buyer_id_threshold' => 150000,
            'default_payment_method_code' => 'C',
            'eta_receipt_type' => 'SC',
            'eta_type_version' => '1.2',
            'eta_seller_name' => $branch?->restaurant?->name,
            'eta_branch_code' => $branch?->id ? (string) $branch->id : null,
            'address_country' => 'EG',
            'address_region_city' => $branch?->location,
        ]);
    }
}
