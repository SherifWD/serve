<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ComboFactory extends Factory
{
    public function definition()
    {
        return [
            'menu_id' => 1, // update as needed
            'name' => 'Combo ' . $this->faker->word,
            'price' => $this->faker->randomFloat(2, 20, 300),
        ];
    }
}
