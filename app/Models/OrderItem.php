<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory; protected $guarded =[];
    public function order() {
    return $this->belongsTo(Order::class);
}

public function product() {
    return $this->belongsTo(Product::class);
}
public function answers()
{
    return $this->hasMany(CategoryAnswer::class);
}
public function modifier()
{
    return $this->hasMany(OrderItemModifier::class);
}
public function modifiers()
{
    return $this->hasMany(\App\Models\OrderItemModifier::class);
}

}
