<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SettingFactory extends Factory
{
    public function definition()
    {
        return [
            'key' => $this->faker->unique()->word,
            'value' => $this->faker->word,
        ];
    }
}
