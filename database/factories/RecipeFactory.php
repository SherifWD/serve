<?php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class RecipeFactory extends Factory
{
    public function definition()
    {
        return [
            'product_id' => 1, // update as needed
            'description' => $this->faker->sentence,
        ];
    }
}
