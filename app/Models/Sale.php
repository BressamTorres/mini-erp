<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Database\Factories\SaleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['customer_id', 'seller_id', 'total', 'active', 'external_order_id'])]
class Sale extends Model
{
    /** @use HasFactory<SaleFactory> */
    use BelongsToBusiness, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Person, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'customer_id')
            ->where('type', Person::TYPE_CUSTOMER);
    }

    /** @return BelongsTo<Person, $this> */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'seller_id')
            ->where('type', Person::TYPE_SELLER);
    }

    /** @return HasMany<SaleItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class)->orderBy('id');
    }

    public function formattedNumber(): string
    {
        return '#'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function formattedTotal(): string
    {
        return 'R$ '.number_format((float) $this->total, 2, ',', '.');
    }
}
