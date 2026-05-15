<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory; protected $guarded =[];

    protected $casts = [
        'capabilities' => 'array',
        'is_active' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

public function branch() {
    return $this->belongsTo(Branch::class);
}

public function printJobs() {
    return $this->hasMany(PrintJob::class);
}

public function paymentAttempts() {
    return $this->hasMany(PaymentAttempt::class);
}

}
