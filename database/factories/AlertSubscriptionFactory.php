<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AlertSubscriptionFactory extends Factory
{
    public function definition()
    {
        return [
            'user_id' => 1, // update as needed
            'alert_type' => $this->faker->randomElement(['stock', 'sales', 'system']),
        ];
    }
}
