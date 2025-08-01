<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryItemFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => $this->faker->word,
            'branch_id' => 1, // update as needed
            'quantity' => $this->faker->numberBetween(1, 1000),
            'unit' => $this->faker->randomElement(['g', 'kg', 'ml', 'l']),
            'min_stock' => $this->faker->numberBetween(1, 20),
        ];
    }
}
