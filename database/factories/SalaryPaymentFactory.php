<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SalaryPaymentFactory extends Factory
{
    public function definition()
    {
        return [
            'salary_id' => 1, // update as needed
            'paid_at' => $this->faker->dateTimeThisYear,
            'amount' => $this->faker->numberBetween(2000, 8000),
        ];
    }
}
