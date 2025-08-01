<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'user_id' => 2,
            'branch_id' => 1, // update as needed
            'position' => $this->faker->jobTitle,
            'hired_at' => $this->faker->date(),
            'base_salary' => 100000,
        ];
    }
}
