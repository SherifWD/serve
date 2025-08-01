<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory; protected $guarded =[];
    public function loyaltyTransactions() {
    return $this->hasMany(LoyaltyTransaction::class);
}

public function feedback() {
    return $this->hasMany(Feedback::class);
}

}
