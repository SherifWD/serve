<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseFactory extends Factory
{
    public function definition()
    {
        return [
            'branch_id' => 1, // update as needed
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'description' => $this->faker->sentence,
            'expense_date' => $this->faker->date(),
        ];
    }
}
