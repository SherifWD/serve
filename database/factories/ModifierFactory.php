<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ModifierFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => $this->faker->word,
            'price' => $this->faker->randomFloat(2, 1, 20),
        ];
    }
}
