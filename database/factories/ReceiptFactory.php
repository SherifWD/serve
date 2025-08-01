<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ReceiptFactory extends Factory
{
    public function definition()
    {
        return [
            'order_id' => 1, // update as needed
            'receipt_number' => $this->faker->unique()->bothify('RCPT-#####'),
            'total' => $this->faker->randomFloat(2, 20, 500),
            'issued_at' => $this->faker->dateTimeThisMonth,
        ];
    }
}
