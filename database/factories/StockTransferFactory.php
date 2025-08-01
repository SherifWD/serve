<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class StockTransferFactory extends Factory
{
    public function definition()
    {
        return [
            'from_branch_id' => 1, // update as needed
            'to_branch_id' => 2, // update as needed
            'inventory_item_id' => 1, // update as needed
            'quantity' => $this->faker->numberBetween(1, 100),
            'transfer_date' => $this->faker->date(),
        ];
    }
}
