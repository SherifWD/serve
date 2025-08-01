<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class StockAlertFactory extends Factory
{
    public function definition()
    {
        return [
            'inventory_item_id' => 1, // update as needed
            'branch_id' => 1, // update as needed
            'threshold' => $this->faker->numberBetween(1, 10),
            'alerted_at' => $this->faker->dateTimeThisMonth,
        ];
    }
}
