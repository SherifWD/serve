<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory; protected $guarded =[];
    public function products() {
    return $this->hasMany(Product::class);
}

public function branch() {
    return $this->belongsTo(Branch::class);
}
public function menus()
{
    return $this->belongsToMany(\App\Models\Menu::class, 'category_menu');
}

public function questions() {
    return $this->hasMany(CategoryQuestion::class);
}
}
