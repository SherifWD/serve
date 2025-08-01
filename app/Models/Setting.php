<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory; protected $guarded =[];
    // Most settings are simple key-value and may not need relationships.
// But for per-branch or per-user settings:
public function branch() {
    return $this->belongsTo(Branch::class);
}

public function user() {
    return $this->belongsTo(User::class);
}

}
