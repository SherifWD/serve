<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BankAccountFactory extends Factory
{
    public function definition()
    {
        return [
            'bank_name' => $this->faker->company,
            'account_number' => $this->faker->bankAccountNumber,
            'balance' => $this->faker->randomFloat(2, 500, 50000),
        ];
    }
}
