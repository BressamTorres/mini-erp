<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['sku', 'name', 'sale_price', 'active', 'photo_path'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sale_price' => 'decimal:2',
            'active' => 'boolean',
        ];
    }

    public function formattedSalePrice(): string
    {
        return 'R$ '.number_format((float) $this->sale_price, 2, ',', '.');
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path === null
            ? null
            : '/storage/'.ltrim($this->photo_path, '/');
    }
}
