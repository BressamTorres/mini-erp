<?php

namespace App\Models;

use App\Services\BusinessSlugService;
use Database\Factories\BusinessFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['trade_name', 'legal_name', 'document', 'phone', 'email'])]
#[Hidden(['catalog_api_token'])]
class Business extends Model
{
    /** @use HasFactory<BusinessFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Business $business): void {
            if ($business->catalog_slug === null || $business->catalog_slug === '') {
                $business->catalog_slug = app(BusinessSlugService::class)
                    ->generate($business->trade_name);
            }
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'catalog_api_token_expires_at' => 'datetime',
        ];
    }

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** @return HasMany<Person, $this> */
    public function people(): HasMany
    {
        return $this->hasMany(Person::class);
    }

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /** @return HasMany<Sale, $this> */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
