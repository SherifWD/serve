<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BranchFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => 'Branch ' . $this->faker->city,
            'location' => $this->faker->address,
        ];
    }
}
