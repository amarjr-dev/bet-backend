<?php

namespace Database\Factories;

use App\Models\Gateway;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Gateway>
 */
class GatewayFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'        => fake()->randomElement(['Gateway 1', 'Gateway 2']),
            'driver'      => 'Gateway1Adapter',
            'is_active'   => true,
            'priority'    => fake()->unique()->numberBetween(1, 10),
            'credentials' => json_encode([
                'url'   => 'http://localhost:3001',
                'email' => 'test@test.com',
                'token' => 'secret',
            ]),
        ];
    }
}
