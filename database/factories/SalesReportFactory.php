<?php


namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SalesReportFactory extends Factory
{
    public function definition()
    {
        return [
            'branch_id' => 1, // update as needed
            'report_date' => $this->faker->date(),
            'total_sales' => $this->faker->randomFloat(2, 500, 20000),
        ];
    }
}
