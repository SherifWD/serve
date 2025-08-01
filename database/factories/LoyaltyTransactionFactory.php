<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class LoyaltyTransactionFactory extends Factory
{
    public function definition()
    {
        return [
            'customer_id' => 1, // update as needed
            'points' => $this->faker->numberBetween(1, 100),
            'type' => $this->faker->randomElement(['earn', 'redeem']),
            'transaction_date' => $this->faker->date(),
        ];
    }
}
