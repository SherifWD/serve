<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class WebhookLogFactory extends Factory
{
    public function definition()
    {
        return [
            'event' => $this->faker->word,
            'payload' => json_encode(['example' => $this->faker->word]),
            'received_at' => $this->faker->dateTimeThisMonth,
        ];
    }
}
