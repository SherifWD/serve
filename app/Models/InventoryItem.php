<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    use HasFactory; protected $guarded =[];
    public function branch() {
    return $this->belongsTo(Branch::class);
}

public function transactions() {
    return $this->hasMany(InventoryTransaction::class);
}

public function stockAlerts() {
    return $this->hasMany(StockAlert::class);
}
public function ingredient() {
    return $this->belongsTo(Ingredient::class);
}
public function product() {
    return $this->belongsTo(Product::class);
}

}
