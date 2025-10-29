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
            'status' => $this->faker->randomElement(['pending', 'preparing', 'prepared', 'paid', 'cancelled', 'refunded']),
            'order_type' => $this->faker->randomElement(['dine-in', 'takeaway', 'delivery']),
            'total' => $this->faker->randomFloat(2, 20, 5000),
            'subtotal' => $this->faker->randomFloat(2, 20, 4000),
            'tax' => $this->faker->randomFloat(2, 0, 500),
            'discount' => $this->faker->randomFloat(2, 0, 200),
            'order_date' => $this->faker->dateTimeThisMonth,
        ];
    }
}
