<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItemModifier extends Model
{
    protected $guarded =[];
    public function modifier()
{
    return $this->belongsTo(Modifier::class,'modifier_id');
}

}
