<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class DeviceFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => 'Device ' . $this->faker->unique()->word,
            'branch_id' => 1, // update as needed
            'type' => $this->faker->randomElement(['POS', 'Tablet', 'Kiosk']),
            'uuid' => rand(90000, 1000000),
        ];
    }
}
