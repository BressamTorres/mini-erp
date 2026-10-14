<?php

namespace App\Models;

use Database\Factories\PersonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'document', 'phone', 'email', 'photo_path', 'photo_disk', 'type', 'active', 'system_key'])] class Person extends Model
{
    /** @use HasFactory<PersonFactory> */
    use HasFactory;

    public const TYPE_CUSTOMER = 'customer';

    public const TYPE_SELLER = 'seller';

    public const SYSTEM_DEFAULT_CUSTOMER = 'default_customer';

    public const SYSTEM_DEFAULT_SELLER = 'default_seller';

    public const TYPES = [
        self::TYPE_CUSTOMER,
        self::TYPE_SELLER,
    ];

    protected $table = 'persons';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function formattedDocument(): ?string
    {
        if ($this->document === null) {
            return null;
        }

        if (strlen($this->document) === 11) {
            return preg_replace('/^(\d{3})(\d{3})(\d{3})(\d{2})$/', '$1.$2.$3-$4', $this->document) ?? $this->document;
        }

        if (strlen($this->document) === 14) {
            return preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/', '$1.$2.$3/$4-$5', $this->document) ?? $this->document;
        }

        return $this->document;
    }

    public function formattedPhone(): string
    {
        if (strlen($this->phone) === 10) {
            return preg_replace('/^(\d{2})(\d{4})(\d{4})$/', '($1) $2-$3', $this->phone) ?? $this->phone;
        }

        if (strlen($this->phone) === 11) {
            return preg_replace('/^(\d{2})(\d{5})(\d{4})$/', '($1) $2-$3', $this->phone) ?? $this->phone;
        }

        return $this->phone;
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path === null
            ? null
            : route('persons.photo', ['person' => $this->id]);
    }

    public function storedPhotoDisk(): string
    {
        return $this->photo_disk ?? (string) config('filesystems.legacy_photo_disk', 'public');
    }

    /** @return HasMany<Sale, $this> */
    public function customerSales(): HasMany
    {
        return $this->hasMany(Sale::class, 'customer_id');
    }

    /** @return HasMany<Sale, $this> */
    public function sellerSales(): HasMany
    {
        return $this->hasMany(Sale::class, 'seller_id');
    }
}
