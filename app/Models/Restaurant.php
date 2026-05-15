<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function fiscalProfiles()
    {
        return $this->hasMany(FiscalProfile::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(RestaurantSubscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(RestaurantSubscription::class)->latestOfMany();
    }

    public function billingInvoices()
    {
        return $this->hasMany(BillingInvoice::class);
    }

    public function paymentProviderConfigs()
    {
        return $this->hasMany(PaymentProviderConfig::class);
    }

    public function paymentAttempts()
    {
        return $this->hasMany(PaymentAttempt::class);
    }

    public function printJobs()
    {
        return $this->hasMany(PrintJob::class);
    }
}
