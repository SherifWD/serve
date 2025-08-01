<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    public function definition()
    {
        return [
            'branch_id' => 1, // update as needed
            'table_id' => 1, // update as needed
            'status' => $this->faker->randomElement(['pending', 'completed', 'cancelled']),
            'total' => $this->faker->randomFloat(2, 20, 5000),
            'order_date' => $this->faker->dateTimeThisMonth,
        ];
    }
}
