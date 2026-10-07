<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sku' => strtoupper(fake()->unique()->bothify('???###')),
            'name' => fake()->words(3, true),
            'sale_price' => fake()->randomFloat(2, 1, 10000),
            'active' => true,
            'photo_path' => null,
        ];
    }
}
