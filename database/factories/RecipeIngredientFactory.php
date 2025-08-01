<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class RecipeIngredientFactory extends Factory
{
    public function definition()
    {
        return [
            'recipe_id' => 1, // update as needed
            'ingredient_id' => 1, // update as needed
            'quantity' => $this->faker->numberBetween(1, 100),
        ];
    }
}
