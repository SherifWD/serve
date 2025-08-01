<?php


namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductPerformanceFactory extends Factory
{
    public function definition()
    {
        return [
            'product_id' => 1, // update as needed
            'period' => $this->faker->monthName . ' ' . $this->faker->year,
            'units_sold' => $this->faker->numberBetween(10, 500),
        ];
    }
}
