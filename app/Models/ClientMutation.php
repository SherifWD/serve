<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientMutation extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'response_payload' => 'array',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }
}
