<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryAnswer extends Model
{
    protected $guarded =[];
    public function choice()
{
    return $this->belongsTo(CategoryChoice::class, 'choice_id');
}

}
