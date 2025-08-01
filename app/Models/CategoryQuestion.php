<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryQuestion extends Model
{
    protected $guarded =[];
    public function choices() {
    return $this->hasMany(CategoryChoice::class, 'question_id');
}
}
