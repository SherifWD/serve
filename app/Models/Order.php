<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\MultipleInstanceManager;

class Order extends Model
{
    use HasFactory; protected $guarded =[];
    public function branch() {
    return $this->belongsTo(Branch::class);
}

public function table() {
    return $this->belongsTo(Table::class);
}

public function items() {
    return $this->hasMany(OrderItem::class);
}


public function payments() {
    return $this->hasMany(Payment::class);
}

public function receipt() {
    return $this->hasOne(Receipt::class);
}

public function statusLogs() {
    return $this->hasMany(OrderStatusLog::class);
}
public function employee()
{
    return $this->belongsTo(Employee::class); // or User::class, depending on your model
}


}
