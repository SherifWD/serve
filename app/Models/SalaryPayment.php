<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryPayment extends Model
{
    use HasFactory; protected $guarded =[];
    public function salary() {
    return $this->belongsTo(Salary::class);
}

}
