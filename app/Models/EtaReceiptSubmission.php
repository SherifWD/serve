<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EtaReceiptSubmission extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'eta_response' => 'array',
            'attempted_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function receipt()
    {
        return $this->belongsTo(Receipt::class);
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
