<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Modifier extends Model
{
    use HasFactory; protected $guarded =[];
    public function menuModifiers() {
    return $this->hasMany(MenuModifier::class);
}

}
