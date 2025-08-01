<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasFactory; protected $guarded =[];
    // No default relationships, but if you link to transactions:
public function transactions() {
    return $this->hasMany(Transaction::class);
}

}
