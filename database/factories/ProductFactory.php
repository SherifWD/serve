<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => $this->faker->word,
            'category_id' => 1, // update as needed
            'branch_id' => 1, // update as needed
            'price' => $this->faker->randomFloat(2, 10, 200),
            'is_available' => 1,
        ];
    }
}
