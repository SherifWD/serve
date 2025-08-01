<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    public function definition()
    {
        return [
            'employee_id' => 1, // update as needed
            'date' => $this->faker->date(),
            'status' => $this->faker->randomElement(['present', 'absent', 'late']),
        ];
    }
}
