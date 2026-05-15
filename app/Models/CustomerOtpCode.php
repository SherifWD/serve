<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerOtpCode extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
