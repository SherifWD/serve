<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MenuFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => 'Menu ' . $this->faker->word,
            'branch_id' => 1, // update as needed
            // 'active' => $this->faker->boolean(80),
        ];
    }
}
