<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use LogicException;

#[Fillable(['name', 'sale_price', 'active', 'photo_path', 'photo_disk'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use BelongsToBusiness, HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Product $product): void {
            $product->setAttribute('sku', null);
        });

        static::created(function (Product $product): void {
            $product->forceFill([
                'sku' => self::skuFromId((int) $product->getKey()),
            ])->saveQuietly();
        });

        static::updating(function (Product $product): void {
            if ($product->isDirty('sku')) {
                throw new LogicException('O SKU do produto não pode ser alterado.');
            }
        });
    }

    public static function skuFromId(int $productId): string
    {
        return str_pad((string) $productId, 6, '0', STR_PAD_LEFT);
    }

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
            : Storage::disk($this->storedPhotoDisk())->url($this->photo_path);
    }

    public function storedPhotoDisk(): string
    {
        return $this->photo_disk ?? (string) config('filesystems.legacy_photo_disk', 'public');
    }

    /** @return HasMany<SaleItem, $this> */
    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}
