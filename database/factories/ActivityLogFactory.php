<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityLogFactory extends Factory
{
    public function definition()
    {
        return [
            'user_id' => 1, // update as needed
            'action' => $this->faker->sentence,
            'logged_at' => $this->faker->dateTimeThisMonth,
        ];
    }
}
