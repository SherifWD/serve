<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TableFactory extends Factory
{
    public function definition()
    {
        return [
            'branch_id' => 1, // update as needed
            'name' => 'Table ' . $this->faker->numberBetween(1, 20),
            'seats' => $this->faker->numberBetween(2, 10),
        ];
    }
}
