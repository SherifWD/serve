<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryTransactionFactory extends Factory
{
    public function definition()
    {
        return [
            'inventory_item_id' => 1, // update as needed
            'type' => $this->faker->randomElement(['in', 'out', 'adjustment']),
            'quantity' => $this->faker->numberBetween(1, 100),
            'created_at' => $this->faker->dateTimeThisMonth,
        ];
    }
}
