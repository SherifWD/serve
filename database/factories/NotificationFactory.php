<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    public function definition()
    {
        return [
            'user_id' => 1, // update as needed
            'title' => $this->faker->sentence(3),
            'body' => $this->faker->sentence,
            'type' => $this->faker->randomElement(['system', 'inventory', 'order']),
            'read_at' => $this->faker->optional()->dateTimeThisMonth,
        ];
    }
}
