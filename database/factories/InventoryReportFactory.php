<?php


namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryReportFactory extends Factory
{
    public function definition()
    {
        return [
            'branch_id' => 1, // update as needed
            'report_date' => $this->faker->date(),
            'item_count' => $this->faker->numberBetween(10, 200),
        ];
    }
}
