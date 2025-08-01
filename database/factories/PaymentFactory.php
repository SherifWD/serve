<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    public function definition()
    {
        return [
            'order_id' => 1, // update as needed
            'amount' => $this->faker->randomFloat(2, 20, 500),
            'method' => $this->faker->randomElement(['cash', 'card', 'wallet']),
            'paid_at' => $this->faker->dateTimeThisMonth,
        ];
    }
}
