<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    use HasFactory; protected $guarded =[];
    // If taxes are applied to orders or products (pivot), you might need:
public function orders() {
    return $this->belongsToMany(Order::class);
}
public function products() {
    return $this->belongsToMany(Product::class);
}

}
