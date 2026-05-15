<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentProviderConfig extends Model
{
    protected $guarded = [];

    protected $hidden = [
        'credentials',
        'webhook_secret',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'credentials' => 'encrypted:array',
            'terminal_config' => 'array',
            'supported_methods' => 'array',
            'metadata' => 'array',
        ];
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
