<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuModifier extends Model
{
    use HasFactory; protected $guarded =[];
    public function menu() {
    return $this->belongsTo(Menu::class);
}

public function modifier() {
    return $this->belongsTo(Modifier::class);
}

}
