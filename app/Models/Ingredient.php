<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    use HasFactory; protected $guarded =[];
    public function recipeIngredients() {
    return $this->hasOne(RecipeIngredient::class);
}
public function recipes()
    {
        return $this->belongsToMany(Recipe::class, 'recipe_ingredients')
                    ->withPivot('quantity')
                    ->withTimestamps();
    }

    public function ingredientBranches()
{
    return $this->hasMany(\App\Models\IngredientBranch::class);
}


}
