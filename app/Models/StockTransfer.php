<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    use HasFactory; protected $guarded =[];
    public function fromBranch() {
    return $this->belongsTo(Branch::class, 'from_branch_id');
}

public function toBranch() {
    return $this->belongsTo(Branch::class, 'to_branch_id');
}

}
