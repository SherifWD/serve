<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    public function definition()
    {
        return [
            'order_id' => 1, // update as needed
            'product_id' => 1, // update as needed
            'quantity' => $this->faker->numberBetween(1, 10),
            'price' => $this->faker->randomFloat(2, 10, 200),
            'total' => $this->faker->randomFloat(2, 100, 20000)
        ];
    }
}
