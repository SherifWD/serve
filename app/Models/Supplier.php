<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory; protected $guarded =[];
public function purchaseOrders() {
    return $this->hasMany(PurchaseOrder::class);
}

public function restaurant() {
    return $this->belongsTo(Restaurant::class);
}

}
