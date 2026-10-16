<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Person;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CatalogSaleControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_401_without_authentication(): void
    {
        $this->postJson(route('api.catalog.sales.store'), [])
            ->assertUnauthorized();
    }

    public function test_returns_401_with_invalid_token(): void
    {
        $this->withToken('invalid-catalog-token')
            ->postJson(route('api.catalog.sales.store'), [])
            ->assertUnauthorized();

        $this->assertDatabaseEmpty('sales');
    }

    public function test_returns_403_when_token_lacks_required_ability(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['catalog:products:read']);

        $this->withHeader('X-Business-ID', (string) $user->business_id)
            ->postJson(route('api.catalog.sales.store'), [])
            ->assertForbidden();
    }

    public function test_returns_403_when_business_header_is_missing(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['catalog:sales:create']);

        $this->postJson(route('api.catalog.sales.store'), [])
            ->assertForbidden()
            ->assertJsonPath('message', 'A empresa informada não corresponde ao token do Catálogo.');
    }

    public function test_returns_429_when_catalog_rate_limit_is_exceeded(): void
    {
        config()->set('services.catalog_api.rate_limit_per_minute', 1);
        $this->authenticateCatalog();

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->postJson(route('api.catalog.sales.store'), [])
            ->assertUnprocessable();
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->postJson(route('api.catalog.sales.store'), [])
            ->assertTooManyRequests();

        $this->assertDatabaseEmpty('sales');
    }

    public function test_returns_422_when_required_fields_are_missing(): void
    {
        $this->authenticateCatalog();

        $this->postJson(route('api.catalog.sales.store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['external_order_id', 'items']);

        $this->assertDatabaseEmpty('sales');
    }

    public function test_returns_422_for_invalid_order_data(): void
    {
        $this->authenticateCatalog();

        $this->postJson(route('api.catalog.sales.store'), [
            'external_order_id' => 'invalid-uuid',
            'customer_id' => 'invalid-customer',
            'seller_id' => 'invalid-seller',
            'items' => [
                ['product_id' => 'invalid-product', 'quantity' => 0],
                ['product_id' => 'invalid-product', 'quantity' => 1],
            ],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors([
                'external_order_id',
                'customer_id',
                'seller_id',
                'items.0.product_id',
                'items.0.quantity',
                'items.1.product_id',
            ]);

        $this->assertDatabaseEmpty('sales');
        $this->assertDatabaseEmpty('sale_items');
    }

    public function test_returns_422_when_customer_is_not_eligible_for_sale(): void
    {
        $this->authenticateCatalog();
        $seller = Person::factory()->seller()->create();
        $product = Product::factory()->create();

        $this->postJson(route('api.catalog.sales.store'), [
            'external_order_id' => (string) Str::uuid(),
            'customer_id' => Person::factory()->seller()->create()->id,
            'seller_id' => $seller->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['customer_id']);

        $this->assertDatabaseEmpty('sales');
    }

    public function test_returns_422_when_product_is_not_eligible_for_sale(): void
    {
        $this->authenticateCatalog();
        $customer = Person::factory()->customer()->create();
        $seller = Person::factory()->seller()->create();
        $product = Product::factory()->create(['active' => false]);

        $this->postJson(route('api.catalog.sales.store'), [
            'external_order_id' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'seller_id' => $seller->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.product_id']);

        $this->assertDatabaseEmpty('sales');
        $this->assertDatabaseEmpty('sale_items');
    }

    public function test_returns_422_when_product_does_not_exist(): void
    {
        $this->authenticateCatalog();
        $customer = Person::factory()->customer()->create();
        $seller = Person::factory()->seller()->create();

        $this->postJson(route('api.catalog.sales.store'), [
            'external_order_id' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'seller_id' => $seller->id,
            'items' => [['product_id' => 999999, 'quantity' => 1]],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.product_id']);

        $this->assertDatabaseEmpty('sales');
        $this->assertDatabaseEmpty('sale_items');
    }

    public function test_creates_sale_with_default_people_and_backend_prices(): void
    {
        $this->authenticateCatalog();
        $product = Product::factory()->create(['sale_price' => '49.90']);
        $externalOrderId = (string) Str::uuid();

        $response = $this->postJson(route('api.catalog.sales.store'), [
            'external_order_id' => $externalOrderId,
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 2,
                'unit_price' => '0.01',
                'total' => '0.02',
            ]],
            'total' => '0.02',
        ]);

        $sale = Sale::query()->firstOrFail();
        $customer = Person::query()->where('system_key', Person::SYSTEM_DEFAULT_CUSTOMER)->firstOrFail();
        $seller = Person::query()->where('system_key', Person::SYSTEM_DEFAULT_SELLER)->firstOrFail();
        $response->assertCreated()->assertExactJson([
            'id' => $sale->id,
            'number' => $sale->formattedNumber(),
            'total' => '99.80',
            'created' => true,
        ]);
        $this->assertSame('Consumidor Final', $customer->name);
        $this->assertSame('Vendas Online', $seller->name);
        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'external_order_id' => $externalOrderId,
            'customer_id' => $customer->id,
            'seller_id' => $seller->id,
            'total' => '99.80',
        ]);
        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => '49.90',
            'total' => '99.80',
        ]);
    }

    public function test_uses_people_provided_by_catalog(): void
    {
        $this->authenticateCatalog();
        $customer = Person::factory()->customer()->create();
        $seller = Person::factory()->seller()->create();
        $product = Product::factory()->create();

        $this->postJson(route('api.catalog.sales.store'), [
            'external_order_id' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'seller_id' => $seller->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertCreated();

        $this->assertDatabaseHas('sales', [
            'customer_id' => $customer->id,
            'seller_id' => $seller->id,
        ]);
        $this->assertDatabaseMissing('persons', ['system_key' => Person::SYSTEM_DEFAULT_CUSTOMER]);
        $this->assertDatabaseMissing('persons', ['system_key' => Person::SYSTEM_DEFAULT_SELLER]);
    }

    public function test_selected_people_items_and_totals_are_persisted_with_integrity(): void
    {
        $this->authenticateCatalog();
        $customer = Person::factory()->customer()->create();
        $seller = Person::factory()->seller()->create();
        $firstProduct = Product::factory()->create(['sale_price' => '19.90']);
        $secondProduct = Product::factory()->create(['sale_price' => '5.25']);
        $externalOrderId = (string) Str::uuid();

        $response = $this->postJson(route('api.catalog.sales.store'), [
            'external_order_id' => $externalOrderId,
            'customer_id' => $customer->id,
            'seller_id' => $seller->id,
            'items' => [
                ['product_id' => $firstProduct->id, 'quantity' => 2],
                ['product_id' => $secondProduct->id, 'quantity' => 3],
            ],
        ]);

        $sale = Sale::query()->with('items')->firstOrFail();
        $response->assertCreated()->assertExactJson([
            'id' => $sale->id,
            'number' => $sale->formattedNumber(),
            'total' => '55.55',
            'created' => true,
        ]);
        $this->assertSame($customer->id, $sale->customer_id);
        $this->assertSame($seller->id, $sale->seller_id);
        $this->assertSame($externalOrderId, $sale->external_order_id);
        $this->assertCount(2, $sale->items);
        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id,
            'product_id' => $firstProduct->id,
            'quantity' => 2,
            'unit_price' => '19.90',
            'total' => '39.80',
        ]);
        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id,
            'product_id' => $secondProduct->id,
            'quantity' => 3,
            'unit_price' => '5.25',
            'total' => '15.75',
        ]);
    }

    public function test_uses_current_erp_price_when_price_changes_before_confirmation(): void
    {
        $this->authenticateCatalog();
        $product = Product::factory()->create(['sale_price' => '10.00']);
        $product->update(['sale_price' => '27.50']);

        $response = $this->postJson(route('api.catalog.sales.store'), [
            'external_order_id' => (string) Str::uuid(),
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 2,
                'unit_price' => '10.00',
                'total' => '20.00',
            ]],
            'total' => '20.00',
        ]);

        $response->assertCreated()->assertJsonPath('total', '55.00');
        $this->assertDatabaseHas('sale_items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => '27.50',
            'total' => '55.00',
        ]);
        $this->assertDatabaseHas('sales', ['total' => '55.00']);
    }

    public function test_same_external_order_returns_existing_sale_without_duplication(): void
    {
        $this->authenticateCatalog();
        $product = Product::factory()->create(['sale_price' => '12.50']);
        $payload = [
            'external_order_id' => (string) Str::uuid(),
            'items' => [['product_id' => $product->id, 'quantity' => 3]],
        ];

        $createdResponse = $this->postJson(route('api.catalog.sales.store'), $payload)->assertCreated();
        $existingResponse = $this->postJson(route('api.catalog.sales.store'), array_merge($payload, [
            'total' => '0.01',
        ]))->assertOk();

        $this->assertSame($createdResponse->json('id'), $existingResponse->json('id'));
        $this->assertFalse($existingResponse->json('created'));
        $this->assertSame('37.50', $existingResponse->json('total'));
        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseCount('sale_items', 1);
    }

    public function test_returns_409_when_external_order_has_different_content(): void
    {
        $this->authenticateCatalog();
        $product = Product::factory()->create(['sale_price' => '20.00']);
        $payload = [
            'external_order_id' => (string) Str::uuid(),
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ];

        $this->postJson(route('api.catalog.sales.store'), $payload)->assertCreated();
        $payload['items'][0]['quantity'] = 2;

        $this->postJson(route('api.catalog.sales.store'), $payload)
            ->assertConflict()
            ->assertExactJson([
                'message' => 'O identificador externo já está vinculado a um pedido diferente.',
            ]);

        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseHas('sale_items', [
            'product_id' => $product->id,
            'quantity' => 1,
            'total' => '20.00',
        ]);
    }

    public function test_same_external_order_returns_existing_sale_after_entities_are_deactivated(): void
    {
        $this->authenticateCatalog();
        $customer = Person::factory()->customer()->create();
        $seller = Person::factory()->seller()->create();
        $product = Product::factory()->create(['sale_price' => '15.00']);
        $payload = [
            'external_order_id' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'seller_id' => $seller->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ];

        $createdResponse = $this->postJson(route('api.catalog.sales.store'), $payload)->assertCreated();
        $customer->update(['active' => false]);
        $seller->update(['active' => false]);
        $product->update(['active' => false]);

        $existingResponse = $this->postJson(route('api.catalog.sales.store'), $payload)->assertOk();

        $this->assertSame($createdResponse->json('id'), $existingResponse->json('id'));
        $this->assertFalse($existingResponse->json('created'));
        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseCount('sale_items', 1);
    }

    private function authenticateCatalog(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['catalog:sales:create']);
        $this->withHeader('X-Business-ID', (string) $user->business_id);
    }
}
