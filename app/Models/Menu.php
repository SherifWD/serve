<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory; protected $guarded =[];
    public function branch() {
    return $this->belongsTo(Branch::class);
}

public function combos() {
    return $this->hasMany(Combo::class);
}
// Menu.php
public function categories() {
    return $this->belongsToMany(Category::class, 'category_menu');
}
public function availabilities() {
    return $this->hasMany(MenuAvailability::class);
}

public function menuModifiers() {
    return $this->hasMany(MenuModifier::class);
}

}
