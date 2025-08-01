<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class DiscountFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => $this->faker->word,
            'amount' => $this->faker->randomFloat(2, 1, 50),
            'type' => $this->faker->randomElement(['percent', 'fixed']),
        ];
    }
}
