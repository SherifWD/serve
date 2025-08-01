<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlertSubscription extends Model
{
    use HasFactory; protected $guarded =[];
    public function alert() {
    return $this->belongsTo(Alert::class);
}

public function user() {
    return $this->belongsTo(User::class);
}

}
