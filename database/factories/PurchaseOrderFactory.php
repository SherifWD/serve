<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseOrderFactory extends Factory
{
    public function definition()
    {
        return [
            'branch_id' => 1, // update as needed
            'supplier_id' => 1, // update as needed
            'order_date' => $this->faker->date(),
            'total_cost' => $this->faker->randomFloat(2, 100, 10000),
        ];
    }
}
