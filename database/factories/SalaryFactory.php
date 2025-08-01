<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SalaryFactory extends Factory
{
    public function definition()
    {
        return [
            'employee_id' => 1, // update as needed
            'amount' => $this->faker->numberBetween(2000, 8000),
            'month' => $this->faker->monthName,
            'year' => $this->faker->year,
        ];
    }
}
