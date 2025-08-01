<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory; protected $guarded =[];
    public function category() {
    return $this->belongsTo(Category::class);
}

public function branch() {
    return $this->belongsTo(Branch::class);
}

public function recipe() {
    return $this->hasOne(Recipe::class,'product_id');
}

public function orderItems() {
    return $this->hasMany(OrderItem::class);
}
public function getImageAttribute($val)
    {
        return asset('storage').'/'. $val;
    }
}
