<?php

namespace App\Services;

use App\Models\Person;
use Illuminate\Support\Facades\DB;

class DefaultPersonService
{
    public function customer(): Person
    {
        return $this->findOrCreate(
            Person::SYSTEM_DEFAULT_CUSTOMER,
            Person::TYPE_CUSTOMER,
            'Consumidor Final'
        );
    }

    public function seller(): Person
    {
        return $this->findOrCreate(
            Person::SYSTEM_DEFAULT_SELLER,
            Person::TYPE_SELLER,
            'Vendas Online'
        );
    }

    private function findOrCreate(string $key, string $type, string $name): Person
    {
        $businessId = app(BusinessContext::class)->id();

        DB::table('persons')->insertOrIgnore([
            'business_id' => $businessId,
            'system_key' => $key,
            'type' => $type,
            'name' => $name,
            'phone' => '',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Person::query()
            ->where('system_key', $key)
            ->firstOrFail();
    }
}
