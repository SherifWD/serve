<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MenuAvailabilityFactory extends Factory
{
    public function definition()
    {
        return [
            'menu_id' => 1, // update as needed
            'branch_id' => 1, // update as needed
            'available_from' => $this->faker->time('H:i:s'),
            'available_to' => $this->faker->time('H:i:s'),
            'days' => $this->faker->randomElement(['all', 'weekdays', 'weekends']),
        ];
    }
}
