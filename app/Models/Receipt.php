<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    use HasFactory; protected $guarded =[];

    public function order() {
    return $this->belongsTo(Order::class);
}

public function etaSubmissions() {
    return $this->hasMany(EtaReceiptSubmission::class);
}

public function printJobs() {
    return $this->hasMany(PrintJob::class);
}

}
