<?php

namespace Database\Factories;

use App\Enums\TransactionStatus;
use App\Models\Client;
use App\Models\Gateway;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_id'        => Client::factory(),
            'gateway_id'       => Gateway::factory(),
            'external_id'      => fake()->uuid(),
            'status'           => TransactionStatus::Approved,
            'amount'           => fake()->numberBetween(1000, 99900),
            'card_last_numbers'=> fake()->numerify('####'),
        ];
    }
}
