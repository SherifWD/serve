<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    public function definition()
    {
        return [
            'branch_id' => 1, // update as needed
            'type' => $this->faker->randomElement(['income', 'expense', 'transfer']),
            'amount' => $this->faker->randomFloat(2, 10, 3000),
            'description' => $this->faker->sentence,
            'transaction_date' => $this->faker->date(),
        ];
    }
}
