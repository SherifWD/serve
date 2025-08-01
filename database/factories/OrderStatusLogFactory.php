<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class OrderStatusLogFactory extends Factory
{
    public function definition()
    {
        return [
            'order_id' => 1, // update as needed
            'status' => $this->faker->randomElement(['pending', 'completed', 'cancelled']),
            'changed_at' => $this->faker->dateTimeThisMonth,
            'note' => $this->faker->sentence,
        ];
    }
}
