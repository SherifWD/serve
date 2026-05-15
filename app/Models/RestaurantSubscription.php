<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RestaurantSubscription extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'current_period_starts_at' => 'datetime',
            'current_period_ends_at' => 'datetime',
            'next_invoice_at' => 'datetime',
            'cancel_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function invoices()
    {
        return $this->hasMany(BillingInvoice::class);
    }
}
