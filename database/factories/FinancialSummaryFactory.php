<?php


namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class FinancialSummaryFactory extends Factory
{
    public function definition()
    {
        return [
            'branch_id' => 1, // update as needed
            'period' => $this->faker->monthName . ' ' . $this->faker->year,
            'total_income' => $this->faker->randomFloat(2, 1000, 30000),
            'total_expense' => $this->faker->randomFloat(2, 500, 25000),
        ];
    }
}