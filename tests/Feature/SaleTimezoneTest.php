<?php

namespace Tests\Feature;

use App\Models\Person;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SaleTimezoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_direct_sale_is_stored_and_read_in_sao_paulo_time(): void
    {
        $this->travelTo(CarbonImmutable::create(2026, 10, 14, 10, 15, 30, 'America/Sao_Paulo'));
        $user = User::factory()->create();
        $customer = Person::factory()->customer()->create();
        $seller = Person::factory()->seller()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)->post(route('sales.store'), [
            'customer_id' => $customer->id,
            'seller_id' => $seller->id,
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 1,
            ]],
        ])->assertRedirect(route('sales.index'));

        $sale = Sale::query()->firstOrFail();

        $this->assertSame('America/Sao_Paulo', config('app.timezone'));
        $this->assertSame('2026-10-14 10:15:30', $sale->getRawOriginal('created_at'));
        $this->assertSame('2026-10-14 10:15:30', $sale->getRawOriginal('updated_at'));
        $this->get(route('sales.index'))
            ->assertOk()
            ->assertSee('14/10/2026 10:15');
    }

    public function test_catalog_sale_is_stored_and_read_in_sao_paulo_time(): void
    {
        $this->travelTo(CarbonImmutable::create(2026, 10, 14, 10, 45, 12, 'America/Sao_Paulo'));
        $catalogUser = User::factory()->create();
        Sanctum::actingAs($catalogUser, ['catalog:sales:create']);
        $product = Product::factory()->create(['sale_price' => '25.90']);

        $this->withHeader('X-Business-ID', (string) $catalogUser->business_id)
            ->postJson(route('api.catalog.sales.store'), [
                'external_order_id' => (string) Str::uuid(),
                'items' => [[
                    'product_id' => $product->id,
                    'quantity' => 2,
                ]],
            ])->assertCreated();

        $sale = Sale::query()->firstOrFail();

        $this->assertSame('2026-10-14 10:45:12', $sale->getRawOriginal('created_at'));
        $this->assertSame('2026-10-14 10:45:12', $sale->getRawOriginal('updated_at'));

        $this->actingAs(User::factory()->create())->get(route('sales.index'))
            ->assertOk()
            ->assertSee('14/10/2026 10:45');
    }
}
