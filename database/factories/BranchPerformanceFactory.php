<?php


namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BranchPerformanceFactory extends Factory
{
    public function definition()
    {
        return [
            'branch_id' => 1, // update as needed
            'period' => $this->faker->monthName . ' ' . $this->faker->year,
            'performance_score' => $this->faker->numberBetween(1, 100),
        ];
    }
}
