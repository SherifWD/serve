<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class IncomeFactory extends Factory
{
    public function definition()
    {
        return [
            'branch_id' => 1, // update as needed
            'amount' => $this->faker->randomFloat(2, 10, 2000),
            'source' => $this->faker->word,
            'income_date' => $this->faker->date(),
        ];
    }
}
