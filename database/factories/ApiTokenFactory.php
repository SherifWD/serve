<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ApiTokenFactory extends Factory
{
    public function definition()
    {
        return [
            'user_id' => 1, // update as needed
            'token' => Str::random(60),
            'expires_at' => $this->faker->dateTimeBetween('now', '+1 year'),
        ];
    }
}
