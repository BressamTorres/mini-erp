<?php

namespace Database\Factories;

use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Business>
 */
class BusinessFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trade_name' => fake()->company(),
            'legal_name' => fake()->company().' Ltda.',
            'document' => fake()->unique()->numerify('##############'),
            'phone' => fake()->numerify('###########'),
            'email' => fake()->unique()->companyEmail(),
        ];
    }
}
