<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory; protected $guarded =[];
    public function supplier() {
    return $this->belongsTo(Supplier::class);
}

public function branch() {
    return $this->belongsTo(Branch::class);
}

}
