<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IngredientBranch extends Model
{
    use HasFactory;
    protected $guarded =[];

    protected $fillable = ['ingredient_id', 'branch_id', 'stock'];

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
