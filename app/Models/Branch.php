<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory; protected $guarded =[];
    public function users() {
    return $this->hasMany(User::class);
}

public function devices() {
    return $this->hasMany(Device::class);
}

public function employees() {
    return $this->hasMany(Employee::class);
}

public function products() {
    return $this->hasMany(Product::class);
}

}
