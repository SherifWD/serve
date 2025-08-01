<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class IngredientFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => $this->faker->word,
            'unit' => $this->faker->randomElement(['g', 'kg', 'ml', 'l']),
        ];
    }
}
