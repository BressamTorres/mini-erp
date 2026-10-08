<?php

namespace Database\Factories;

use App\Models\Person;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Person::factory()->customer()->state(['active' => true]),
            'seller_id' => Person::factory()->seller()->state(['active' => true]),
            'total' => '0.00',
            'active' => true,
            'created_at' => fake()->dateTimeBetween('-1 year'),
            'updated_at' => fn (array $attributes) => $attributes['created_at'],
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['active' => false]);
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Sale $sale): void {
            if (! $sale->items()->exists()) {
                SaleItemFactory::new()->for($sale)->create();
            }

            $sale->updateQuietly([
                'total' => $sale->items()->sum('total'),
            ]);
        });
    }
}
