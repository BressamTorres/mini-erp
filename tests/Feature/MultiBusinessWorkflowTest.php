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
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MultiBusinessWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_sales_workflows_and_dashboards_are_isolated_by_business(): void
    {
        [$firstBusiness, $firstUser] = $this->currentBusinessAndUser();

        $this->actingAs($firstUser)->post(route('customers.store'), $this->personPayload('Cliente Alfa'))
            ->assertRedirectToRoute('customers.index');
        $this->actingAs($firstUser)->post(route('sellers.store'), $this->personPayload('Vendedor Alfa'))
            ->assertRedirectToRoute('sellers.index');
        $this->actingAs($firstUser)->post(route('products.store'), $this->productPayload('ALFA-1', 'Produto Alfa', '10,25'))
            ->assertRedirectToRoute('products.index');

        $firstCustomer = Person::query()->where('type', Person::TYPE_CUSTOMER)->sole();
        $firstSeller = Person::query()->where('type', Person::TYPE_SELLER)->sole();
        $firstProduct = Product::query()->sole();

        $this->actingAs($firstUser)->post(route('sales.store'), [
            'customer_id' => $firstCustomer->id,
            'seller_id' => $firstSeller->id,
            'items' => [['product_id' => $firstProduct->id, 'quantity' => 2]],
        ])->assertRedirectToRoute('sales.index');
        $firstSale = Sale::query()->with('items')->sole();

        [$secondBusiness, $secondUser] = $this->anotherBusinessAndUser();
        $this->actingAs($secondUser)->post(route('customers.store'), $this->personPayload('Cliente Beta'))
            ->assertRedirectToRoute('customers.index');
        $this->actingAs($secondUser)->post(route('sellers.store'), $this->personPayload('Vendedor Beta'))
            ->assertRedirectToRoute('sellers.index');
        $this->actingAs($secondUser)->post(route('products.store'), $this->productPayload('BETA-1', 'Produto Beta', '7,00'))
            ->assertRedirectToRoute('products.index');

        [$secondCustomer, $secondSeller, $secondProduct] = app(BusinessContext::class)->run(
            $secondBusiness,
            fn (): array => [
                Person::query()->where('type', Person::TYPE_CUSTOMER)->sole(),
                Person::query()->where('type', Person::TYPE_SELLER)->sole(),
                Product::query()->sole(),
            ],
        );

        $this->actingAs($secondUser)->post(route('sales.store'), [
            'customer_id' => $secondCustomer->id,
            'seller_id' => $secondSeller->id,
            'items' => [['product_id' => $secondProduct->id, 'quantity' => 3]],
        ])->assertRedirectToRoute('sales.index');

        $this->actingAs($firstUser)->put(route('sales.update', $firstSale), [
            'customer_id' => $firstCustomer->id,
            'seller_id' => $firstSeller->id,
            'active' => '1',
            'items' => [[
                'sale_item_id' => $firstSale->items->sole()->id,
                'product_id' => $firstProduct->id,
                'quantity' => 4,
            ]],
        ])->assertRedirectToRoute('sales.index');

        $this->actingAs($firstUser)->get(route('customers.index'))
            ->assertSee('Cliente Alfa')
            ->assertDontSee('Cliente Beta');
        $this->actingAs($firstUser)->get(route('sellers.index'))
            ->assertSee('Vendedor Alfa')
            ->assertDontSee('Vendedor Beta');
        $this->actingAs($firstUser)->get(route('products.index'))
            ->assertSee('Produto Alfa')
            ->assertSee('R$ 10,25')
            ->assertDontSee('Produto Beta')
            ->assertDontSee('R$ 7,00');
        $this->actingAs($firstUser)->get(route('sales.index'))
            ->assertSee('Cliente Alfa')
            ->assertDontSee('Cliente Beta');
        $this->actingAs($firstUser)->get(route('dashboard'))
            ->assertViewHas('indicators', [
                'totalSales' => 'R$ 41,00',
                'salesCount' => 1,
                'customers' => 1,
                'productsSold' => 4,
            ]);

        $this->actingAs($secondUser)->get(route('customers.index'))
            ->assertSee('Cliente Beta')
            ->assertDontSee('Cliente Alfa');
        $this->actingAs($secondUser)->get(route('sellers.index'))
            ->assertSee('Vendedor Beta')
            ->assertDontSee('Vendedor Alfa');
        $this->actingAs($secondUser)->get(route('products.index'))
            ->assertSee('Produto Beta')
            ->assertSee('R$ 7,00')
            ->assertDontSee('Produto Alfa')
            ->assertDontSee('R$ 10,25');
        $this->actingAs($secondUser)->get(route('sales.index'))
            ->assertSee('Cliente Beta')
            ->assertDontSee('Cliente Alfa');
        $this->actingAs($secondUser)->get(route('dashboard'))
            ->assertViewHas('indicators', [
                'totalSales' => 'R$ 21,00',
                'salesCount' => 1,
                'customers' => 1,
                'productsSold' => 3,
            ]);

        $this->assertDatabaseHas('sales', ['id' => $firstSale->id, 'business_id' => $firstBusiness->id, 'total' => '41.00']);
        $this->assertDatabaseHas('sales', ['business_id' => $secondBusiness->id, 'total' => '21.00']);
        $this->assertDatabaseCount('sales', 2);
        $this->assertDatabaseCount('sale_items', 2);
    }

    public function test_cross_business_urls_cannot_read_update_or_deactivate_resources(): void
    {
        [$firstBusiness, $firstUser] = $this->currentBusinessAndUser();
        $firstCustomer = Person::factory()->customer()->create();
        $firstSeller = Person::factory()->seller()->create();
        $firstProduct = Product::factory()->create();
        [$secondBusiness] = $this->anotherBusinessAndUser();
        [$secondCustomer, $secondSeller, $secondProduct, $secondSale] = app(BusinessContext::class)->run(
            $secondBusiness,
            fn (): array => [
                Person::factory()->customer()->create(['name' => 'Cliente Protegido']),
                Person::factory()->seller()->create(['name' => 'Vendedor Protegido']),
                Product::factory()->create(['name' => 'Produto Protegido']),
                Sale::factory()->create(),
            ],
        );

        app(BusinessContext::class)->clear();

        $this->actingAs($firstUser)->get(route('persons.photo', $secondCustomer))->assertNotFound();
        $this->actingAs($firstUser)->put(route('customers.update', $secondCustomer), $this->personPayload('Invadido', true))->assertNotFound();
        $this->actingAs($firstUser)->patch(route('customers.deactivate', $secondCustomer))->assertNotFound();
        $this->actingAs($firstUser)->put(route('sellers.update', $secondSeller), $this->personPayload('Invadido', true))->assertNotFound();
        $this->actingAs($firstUser)->patch(route('sellers.deactivate', $secondSeller))->assertNotFound();
        $this->actingAs($firstUser)->put(
            route('products.update', $secondProduct),
            $this->productPayload('INV-1', 'Invadido', '1,00', true),
        )->assertNotFound();
        $this->actingAs($firstUser)->patch(route('products.deactivate', $secondProduct))->assertNotFound();
        $this->actingAs($firstUser)->getJson(route('sales.show', $secondSale))->assertNotFound();
        $this->actingAs($firstUser)->put(route('sales.update', $secondSale), [
            'customer_id' => $firstCustomer->id,
            'seller_id' => $firstSeller->id,
            'active' => '1',
            'items' => [['product_id' => $firstProduct->id, 'quantity' => 1]],
        ])->assertNotFound();
        $this->actingAs($firstUser)->patch(route('sales.deactivate', $secondSale))->assertNotFound();

        app(BusinessContext::class)->run($secondBusiness, function () use ($secondCustomer, $secondSeller, $secondProduct, $secondSale): void {
            $this->assertSame('Cliente Protegido', $secondCustomer->fresh()->name);
            $this->assertSame('Vendedor Protegido', $secondSeller->fresh()->name);
            $this->assertSame('Produto Protegido', $secondProduct->fresh()->name);
            $this->assertTrue($secondCustomer->fresh()->active);
            $this->assertTrue($secondSeller->fresh()->active);
            $this->assertTrue($secondProduct->fresh()->active);
            $this->assertTrue($secondSale->fresh()->active);
        });
        app(BusinessContext::class)->set($firstBusiness);
    }

    #[DataProvider('crossBusinessPersonForeignKeyProvider')]
    public function test_database_rejects_people_from_another_business_in_sales(string $crossBusinessColumn): void
    {
        [$firstBusiness] = $this->currentBusinessAndUser();
        $firstCustomer = Person::factory()->customer()->create();
        $firstSeller = Person::factory()->seller()->create();
        [$secondBusiness] = $this->anotherBusinessAndUser();
        [$secondCustomer, $secondSeller] = app(BusinessContext::class)->run(
            $secondBusiness,
            fn (): array => [
                Person::factory()->customer()->create(),
                Person::factory()->seller()->create(),
            ],
        );
        $customerId = $crossBusinessColumn === 'customer_id' ? $secondCustomer->id : $firstCustomer->id;
        $sellerId = $crossBusinessColumn === 'seller_id' ? $secondSeller->id : $firstSeller->id;

        $this->expectException(QueryException::class);

        DB::table('sales')->insert([
            'business_id' => $firstBusiness->id,
            'customer_id' => $customerId,
            'seller_id' => $sellerId,
            'total' => '10.00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @return array<string, array{string}> */
    public static function crossBusinessPersonForeignKeyProvider(): array
    {
        return [
            'customer from another business' => ['customer_id'],
            'seller from another business' => ['seller_id'],
        ];
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

    /** @return array<string, string> */
    private function personPayload(string $name, bool $includeActive = false): array
    {
        return array_filter([
            'name' => $name,
            'phone' => '44999999999',
            'email' => null,
            'active' => $includeActive ? '1' : null,
        ], fn (mixed $value): bool => $value !== null);
    }

    /** @return array<string, string> */
    private function productPayload(string $sku, string $name, string $salePrice, bool $includeActive = false): array
    {
        return array_filter([
            'sku' => $sku,
            'name' => $name,
            'sale_price' => $salePrice,
            'active' => $includeActive ? '1' : null,
        ], fn (mixed $value): bool => $value !== null);
    }
}
