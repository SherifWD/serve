<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AlertFactory extends Factory
{
    public function definition()
    {
        return [
            'type' => $this->faker->randomElement(['stock', 'sales', 'system']),
            'message' => $this->faker->sentence,
            'created_at' => $this->faker->dateTimeThisMonth,
        ];
    }
}
