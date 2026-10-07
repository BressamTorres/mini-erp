<?php

namespace Database\Factories;

use App\Models\Seller;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Seller>
 */
class SellerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'document' => null,
            'phone' => fake()->numerify('###########'),
            'email' => fake()->optional()->safeEmail(),
            'photo_path' => null,
        ];
    }
}
