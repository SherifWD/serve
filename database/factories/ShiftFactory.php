<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ShiftFactory extends Factory
{
    public function definition()
    {
        return [
            'employee_id' => 1, // update as needed
            'start_time' => $this->faker->dateTimeThisMonth,
            'end_time' => $this->faker->dateTimeThisMonth,
        ];
    }
}
