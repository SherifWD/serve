<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class FeedbackFactory extends Factory
{
    public function definition()
    {
        return [
            'customer_id' => 1, // update as needed
            'message' => $this->faker->sentence,
            'rating' => $this->faker->numberBetween(1, 5),
            'created_at' => $this->faker->dateTimeThisMonth,
        ];
    }
}
