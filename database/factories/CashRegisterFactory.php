<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CashRegisterFactory extends Factory
{
    public function definition()
    {
        return [
            'branch_id' => 1, // update as needed
            'opened_at' => $this->faker->dateTimeThisMonth,
            'closed_at' => $this->faker->optional()->dateTimeThisMonth,
            'opening_balance' => $this->faker->randomFloat(2, 100, 1000),
        ];
    }
}
