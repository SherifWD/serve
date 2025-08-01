<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AuditLogFactory extends Factory
{
    public function definition()
    {
        return [
            'user_id' => 1, // update as needed
            'event' => $this->faker->sentence,
            'created_at' => $this->faker->dateTimeThisMonth,
        ];
    }
}
