<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleItem>
 */
class SaleItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 5);
        $unitPrice = fake()->randomFloat(2, 1, 1000);

        return [
            'sale_id' => Sale::factory(),
            'product_id' => Product::factory()->state(['active' => true]),
            'quantity' => $quantity,
            'unit_price' => number_format($unitPrice, 2, '.', ''),
            'total' => number_format($quantity * $unitPrice, 2, '.', ''),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (SaleItem $item): void {
            $item->sale->updateQuietly([
                'total' => $item->sale->items()->sum('total'),
            ]);
        });
    }
}
