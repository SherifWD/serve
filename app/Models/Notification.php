<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory; protected $guarded =[];
    // For system-wide or targeted notifications, you might have:
public function users() {
    return $this->belongsToMany(User::class);
}

}
