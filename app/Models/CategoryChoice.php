<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryChoice extends Model
{
    public function question() {
    return $this->belongsTo(\App\Models\CategoryQuestion::class, 'question_id');
}
}
