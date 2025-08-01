<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{
    use HasFactory; protected $guarded =[];
    public function product() {
    return $this->belongsTo(Product::class);
}

public function recipeIngredients() {
    return $this->hasMany(RecipeIngredient::class);
}
public function ingredients()
    {
        return $this->belongsToMany(Ingredient::class, 'recipe_ingredients')
                    ->withPivot('quantity')
                    ->withTimestamps();
    }
}
