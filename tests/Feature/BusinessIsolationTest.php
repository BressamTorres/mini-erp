<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Person;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\BusinessContext;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BusinessIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_only_see_records_from_their_business(): void
    {
        [$firstBusiness, $firstUser] = $this->currentBusinessAndUser();
        $firstCustomer = Person::factory()->customer()->create(['name' => 'Cliente Empresa Um']);
        $firstProduct = Product::factory()->create(['name' => 'Produto Empresa Um']);
        [$secondBusiness, $secondUser] = $this->anotherBusinessAndUser();

        app(BusinessContext::class)->run($secondBusiness, function (): void {
            Person::factory()->customer()->create(['name' => 'Cliente Empresa Dois']);
            Product::factory()->create(['name' => 'Produto Empresa Dois']);
        });

        $this->actingAs($firstUser)->get(route('customers.index'))
            ->assertOk()
            ->assertSee($firstCustomer->name)
            ->assertDontSee('Cliente Empresa Dois');
        $this->actingAs($firstUser)->get(route('products.index'))
            ->assertOk()
            ->assertSee($firstProduct->name)
            ->assertDontSee('Produto Empresa Dois');

        app(BusinessContext::class)->run($secondBusiness, function () use ($secondUser): void {
            $this->actingAs($secondUser)->get(route('customers.index'))
                ->assertOk()
                ->assertSee('Cliente Empresa Dois')
                ->assertDontSee('Cliente Empresa Um');
        });

        $this->assertNotSame($firstBusiness->id, $secondBusiness->id);
    }

    public function test_cross_business_ids_cannot_be_read_updated_or_used_in_a_sale(): void
    {
        [$firstBusiness, $firstUser] = $this->currentBusinessAndUser();
        $firstCustomer = Person::factory()->customer()->create();
        $firstSeller = Person::factory()->seller()->create();
        $firstProduct = Product::factory()->create();
        [$secondBusiness] = $this->anotherBusinessAndUser();

        [$secondCustomer, $secondProduct, $secondSale] = app(BusinessContext::class)->run(
            $secondBusiness,
            fn (): array => [
                Person::factory()->customer()->create(),
                Product::factory()->create(),
                Sale::factory()->create(),
            ],
        );

        app(BusinessContext::class)->clear();

        $this->actingAs($firstUser)->get(route('sales.show', $secondSale))->assertNotFound();
        $this->actingAs($firstUser)->put(route('customers.update', $secondCustomer), [
            'name' => 'Tentativa cruzada',
            'phone' => '11999999999',
            'active' => '1',
        ])->assertNotFound();
        $this->actingAs($firstUser)->patch(route('products.deactivate', $secondProduct))->assertNotFound();

        $this->actingAs($firstUser)->post(route('sales.store'), [
            'customer_id' => $firstCustomer->id,
            'seller_id' => $firstSeller->id,
            'items' => [[
                'product_id' => $secondProduct->id,
                'quantity' => 1,
            ]],
        ])->assertSessionHasErrors('items.0.product_id');

        app(BusinessContext::class)->set($firstBusiness);
        $this->assertTrue($firstProduct->fresh()->active);
        $secondCustomerName = app(BusinessContext::class)->run(
            $secondBusiness,
            fn (): string => $secondCustomer->fresh()->name,
        );
        $this->assertNotSame('Tentativa cruzada', $secondCustomerName);
    }

    public function test_catalog_token_creates_sales_only_inside_its_business(): void
    {
        [$firstBusiness, $firstUser] = $this->currentBusinessAndUser();
        $firstProduct = Product::factory()->create();
        [$secondBusiness, $secondUser] = $this->anotherBusinessAndUser();
        [$secondCustomer, $secondSeller, $secondProduct] = app(BusinessContext::class)->run(
            $secondBusiness,
            fn (): array => [
                Person::factory()->customer()->create(),
                Person::factory()->seller()->create(),
                Product::factory()->create(),
            ],
        );
        $externalOrderId = (string) Str::uuid();

        Sanctum::actingAs($firstUser, ['catalog:sales:create']);
        $this->withHeader('X-Business-ID', (string) $secondBusiness->id)
            ->postJson(route('api.catalog.sales.store'), [
                'external_order_id' => $externalOrderId,
                'items' => [['product_id' => $firstProduct->id, 'quantity' => 1]],
            ])->assertForbidden();

        $this->withHeader('X-Business-ID', (string) $firstBusiness->id);
        $this->postJson(route('api.catalog.sales.store'), [
            'external_order_id' => (string) Str::uuid(),
            'customer_id' => $secondCustomer->id,
            'items' => [['product_id' => $firstProduct->id, 'quantity' => 1]],
        ])->assertUnprocessable()->assertJsonValidationErrors('customer_id');
        $this->postJson(route('api.catalog.sales.store'), [
            'external_order_id' => (string) Str::uuid(),
            'seller_id' => $secondSeller->id,
            'items' => [['product_id' => $firstProduct->id, 'quantity' => 1]],
        ])->assertUnprocessable()->assertJsonValidationErrors('seller_id');
        $this->postJson(route('api.catalog.sales.store'), [
            'external_order_id' => $externalOrderId,
            'items' => [['product_id' => $secondProduct->id, 'quantity' => 1]],
        ])->assertUnprocessable()->assertJsonValidationErrors('items.0.product_id');

        $this->postJson(route('api.catalog.sales.store'), [
            'external_order_id' => $externalOrderId,
            'items' => [['product_id' => $firstProduct->id, 'quantity' => 1]],
        ])->assertCreated();

        Sanctum::actingAs($secondUser, ['catalog:sales:create']);
        $this->withHeader('X-Business-ID', (string) $secondBusiness->id);
        $this->postJson(route('api.catalog.sales.store'), [
            'external_order_id' => $externalOrderId,
            'items' => [['product_id' => $secondProduct->id, 'quantity' => 1]],
        ])->assertCreated();

        $sales = Sale::withoutGlobalScopes()
            ->where('external_order_id', $externalOrderId)
            ->get();

        $this->assertCount(2, $sales);
        $this->assertEqualsCanonicalizing(
            [$firstBusiness->id, $secondBusiness->id],
            $sales->pluck('business_id')->all(),
        );
    }

    public function test_administrator_roles_are_scoped_by_business(): void
    {
        [$firstBusiness, $firstUser] = $this->currentBusinessAndUser();
        [$secondBusiness, $secondUser] = $this->anotherBusinessAndUser();

        app(BusinessContext::class)->run($firstBusiness, function () use ($firstUser): void {
            $firstUser->assignRole(Role::findOrCreate('administrador', 'web'));
        });
        app(BusinessContext::class)->run($secondBusiness, function () use ($secondUser): void {
            $secondUser->assignRole(Role::findOrCreate('administrador', 'web'));
        });

        app(BusinessContext::class)->run($firstBusiness, function () use ($firstUser, $secondUser): void {
            $this->assertTrue($firstUser->fresh()->hasRole('administrador'));
            $this->assertFalse($secondUser->fresh()->hasRole('administrador'));
        });
        app(BusinessContext::class)->run($secondBusiness, function () use ($firstUser, $secondUser): void {
            $this->assertFalse($firstUser->fresh()->hasRole('administrador'));
            $this->assertTrue($secondUser->fresh()->hasRole('administrador'));
        });
    }

    public function test_database_rejects_a_product_from_another_business_in_a_sale(): void
    {
        [$firstBusiness] = $this->currentBusinessAndUser();
        $sale = Sale::factory()->create();
        [$secondBusiness] = $this->anotherBusinessAndUser();
        $secondProduct = app(BusinessContext::class)->run(
            $secondBusiness,
            fn (): Product => Product::factory()->create(),
        );

        $this->expectException(QueryException::class);

        DB::table('sale_items')->insert([
            'business_id' => $firstBusiness->id,
            'sale_id' => $sale->id,
            'product_id' => $secondProduct->id,
            'quantity' => 1,
            'unit_price' => '10.00',
            'total' => '10.00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @return array{Business, User} */
    private function currentBusinessAndUser(): array
    {
        $business = Business::query()->findOrFail(app(BusinessContext::class)->id());

        return [$business, User::factory()->create()];
    }

    /** @return array{Business, User} */
    private function anotherBusinessAndUser(): array
    {
        $business = Business::factory()->create();
        $user = app(BusinessContext::class)->run(
            $business,
            fn (): User => User::factory()->create(),
        );

        return [$business, $user];
    }
}
