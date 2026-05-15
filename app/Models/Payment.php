<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory; protected $guarded =[];

    protected $casts = [
        'amount' => 'float',
        'item_ids' => 'array',
    ];

    public function order() {
    return $this->belongsTo(Order::class);
}

public function attempt() {
    return $this->belongsTo(PaymentAttempt::class, 'payment_attempt_id');
}

public function device() {
    return $this->belongsTo(Device::class);
}

}
