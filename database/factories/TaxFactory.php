<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TaxFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => $this->faker->word . ' Tax',
            'rate' => $this->faker->randomFloat(2, 5, 20),
            'type' => $this->faker->randomElement(['vat', 'service']),
        ];
    }
}
