<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'   => fake()->words(3, true),
            'amount' => fake()->numberBetween(1000, 99900),
        ];
    }
}
