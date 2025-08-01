<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeePerformanceFactory extends Factory
{
    public function definition()
    {
        return [
            'employee_id' => 1, // update as needed
            'score' => $this->faker->numberBetween(1, 10),
            'review_date' => $this->faker->date(),
            'notes' => $this->faker->sentence,
        ];
    }
}
