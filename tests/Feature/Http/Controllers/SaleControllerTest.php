<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Person;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Services\SaleService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SaleControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitor_cannot_access_sales(): void
    {
        $sale = Sale::factory()->create();

        $this->get(route('sales.index'))->assertRedirect(route('login'));
        $this->get(route('sales.show', $sale))->assertRedirect(route('login'));
        $this->post(route('sales.store'), [])->assertRedirect(route('login'));
        $this->put(route('sales.update', $sale), [])->assertRedirect(route('login'));
        $this->patch(route('sales.deactivate', $sale))->assertRedirect(route('login'));
        $this->assertTrue($sale->fresh()->active);
    }

    public function test_authenticated_user_can_access_sale_list(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('sales.index'));

        $response->assertOk()
            ->assertSee('Consulte as vendas realizadas e visualize os detalhes de cada pedido.')
            ->assertSee('Nenhuma venda encontrada')
            ->assertSee('colspan="8"', false)
            ->assertSee('Buscar por número, cliente ou vendedor...')
            ->assertSee('Visualizar inativos')
            ->assertSee('Situação')
            ->assertSee('Nova venda')
            ->assertSee('Detalhes da venda')
            ->assertSee('Realizada em')
            ->assertSee('max-h-[calc(100vh-2rem)]', false)
            ->assertSee('x-text="sale?.active ? \'Ativo\' : \'Inativo\'"', false)
            ->assertSee(':src="item.photo_url"', false)
            ->assertSee('x-text="item.unit_price"', false)
            ->assertSee('Subtotal')
            ->assertSee('Registrar venda')
            ->assertSee('role="combobox"', false)
            ->assertSee('name="customer_id"', false)
            ->assertSee('name="seller_id"', false)
            ->assertDontSee('<select id="sale-', false);
    }

    public function test_sale_row_opens_details_and_action_buttons_stop_event_propagation(): void
    {
        $sale = Sale::factory()->create();

        $this->actingAs(User::factory()->create())->get(route('sales.index'))
            ->assertOk()
            ->assertSee('tabindex="0"', false)
            ->assertSee('aria-haspopup="dialog"', false)
            ->assertSee('@keydown.enter.prevent.self=', false)
            ->assertSee('@keydown.space.prevent.self=', false)
            ->assertSee(', $el, true)', false)
            ->assertSee('x-ref="detailsClose"', false)
            ->assertSee('@click.stop=', false)
            ->assertSee('data-details-url="'.route('sales.show', $sale).'"', false)
            ->assertSee('data-update-url="'.route('sales.update', $sale).'"', false)
            ->assertSee('openEdit($el.dataset.detailsUrl, $el.dataset.updateUrl)', false)
            ->assertDontSee('openEdit(@js(route(', false)
            ->assertSee('title="Editar"', false)
            ->assertSee('title="Excluir"', false)
            ->assertDontSee('title="Visualizar"', false);
    }

    public function test_sale_requires_customer(): void
    {
        $seller = Person::factory()->seller()->create();

        $this->expectException(QueryException::class);

        DB::table('sales')->insert([
            'seller_id' => $seller->id,
            'total' => '10.00',
        ]);
    }

    public function test_sale_requires_seller(): void
    {
        $customer = Person::factory()->customer()->create();

        $this->expectException(QueryException::class);

        DB::table('sales')->insert([
            'customer_id' => $customer->id,
            'total' => '10.00',
        ]);
    }

    public function test_sale_factory_creates_at_least_one_item_with_active_entities(): void
    {
        $sale = Sale::factory()->create();
        $item = $sale->items()->with('product')->firstOrFail();

        $this->assertGreaterThanOrEqual(1, $sale->items()->count());
        $this->assertTrue($sale->active);
        $this->assertTrue($sale->customer->active);
        $this->assertTrue($sale->seller->active);
        $this->assertTrue($item->product->active);
    }

    public function test_new_sale_uses_active_database_default(): void
    {
        $sale = $this->createSale();

        $this->assertIsBool($sale->refresh()->active);
        $this->assertTrue($sale->active);
    }

    public function test_new_sale_requires_customer_seller_and_items(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('sales.store'), [])
            ->assertSessionHasErrors(['customer_id', 'seller_id', 'items']);

        $this->assertDatabaseEmpty('sales');
    }

    public function test_new_sale_requires_quantity_of_at_least_one(): void
    {
        $payload = $this->validSalePayload();
        $payload['items'][0]['quantity'] = 0;

        $this->actingAs(User::factory()->create())->post(route('sales.store'), $payload)
            ->assertSessionHasErrors(['items.0.quantity']);

        $this->assertDatabaseEmpty('sales');
    }

    public function test_new_sale_rejects_wrong_person_types(): void
    {
        $payload = $this->validSalePayload();
        $payload['customer_id'] = Person::factory()->seller()->create()->id;
        $payload['seller_id'] = Person::factory()->customer()->create()->id;

        $this->actingAs(User::factory()->create())->post(route('sales.store'), $payload)
            ->assertSessionHasErrors(['customer_id', 'seller_id']);

        $this->assertDatabaseEmpty('sales');
    }

    public function test_new_sale_rejects_inactive_customer_seller_and_product(): void
    {
        $payload = $this->validSalePayload();
        $payload['customer_id'] = Person::factory()->customer()->create(['active' => false])->id;
        $payload['seller_id'] = Person::factory()->seller()->create(['active' => false])->id;
        $payload['items'][0]['product_id'] = Product::factory()->create(['active' => false])->id;

        $this->actingAs(User::factory()->create())->post(route('sales.store'), $payload)
            ->assertSessionHasErrors(['customer_id', 'seller_id', 'items.0.product_id']);

        $this->assertDatabaseEmpty('sales');
        $this->assertDatabaseEmpty('sale_items');
    }

    public function test_inactive_records_are_not_available_as_new_sale_options(): void
    {
        $activeCustomer = Person::factory()->customer()->create();
        $inactiveCustomer = Person::factory()->customer()->create(['active' => false]);
        $activeSeller = Person::factory()->seller()->create();
        $inactiveSeller = Person::factory()->seller()->create(['active' => false]);
        $activeProduct = Product::factory()->create();
        $inactiveProduct = Product::factory()->create(['active' => false]);

        $this->actingAs(User::factory()->create())->get(route('sales.index'))
            ->assertOk()
            ->assertViewHas('customers', fn (array $customers): bool => collect($customers)->pluck('id')->all() === [$activeCustomer->id])
            ->assertViewHas('sellers', fn (array $sellers): bool => collect($sellers)->pluck('id')->all() === [$activeSeller->id])
            ->assertViewHas('products', fn (array $products): bool => collect($products)->pluck('id')->all() === [$activeProduct->id]);

        $this->assertModelExists($inactiveCustomer);
        $this->assertModelExists($inactiveSeller);
        $this->assertModelExists($inactiveProduct);
    }

    public function test_sale_is_created_transactionally_with_backend_prices_and_totals(): void
    {
        $customer = Person::factory()->customer()->create();
        $seller = Person::factory()->seller()->create();
        $firstProduct = Product::factory()->create(['sale_price' => '89.90']);
        $secondProduct = Product::factory()->create(['sale_price' => '159.90']);
        $payload = [
            'customer_id' => $customer->id,
            'seller_id' => $seller->id,
            'items' => [
                [
                    'product_id' => $firstProduct->id,
                    'quantity' => 2,
                    'unit_price' => '0.01',
                    'total' => '0.02',
                ],
                [
                    'product_id' => $secondProduct->id,
                    'quantity' => 1,
                    'unit_price' => '0.01',
                    'total' => '0.01',
                ],
            ],
            'total' => '0.03',
            'active' => '0',
        ];

        $response = $this->actingAs(User::factory()->create())->post(route('sales.store'), $payload);

        $response->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Venda registrada com sucesso.')
            ->assertRedirect(route('sales.index'));
        $sale = Sale::query()->firstOrFail();
        $items = $sale->items()->orderBy('product_id')->get();
        $this->assertTrue($sale->active);
        $this->assertSame('339.70', $sale->total);
        $this->assertCount(2, $items);
        $this->assertSame('89.90', $items[0]->unit_price);
        $this->assertSame('179.80', $items[0]->total);
        $this->assertSame('159.90', $items[1]->unit_price);
        $this->assertSame('159.90', $items[1]->total);
    }

    public function test_invalid_sale_does_not_persist_partial_records(): void
    {
        $payload = $this->validSalePayload();
        $payload['items'][] = ['product_id' => 999999, 'quantity' => 1];

        $this->actingAs(User::factory()->create())->post(route('sales.store'), $payload)
            ->assertSessionHasErrors(['items.1.product_id']);

        $this->assertDatabaseEmpty('sales');
        $this->assertDatabaseEmpty('sale_items');
    }

    public function test_sale_can_be_edited_and_preserves_historical_price_for_existing_item(): void
    {
        $oldCustomer = Person::factory()->customer()->create();
        $oldSeller = Person::factory()->seller()->create();
        $newCustomer = Person::factory()->customer()->create();
        $newSeller = Person::factory()->seller()->create();
        $existingProduct = Product::factory()->create(['sale_price' => '130.00']);
        $newProduct = Product::factory()->create(['sale_price' => '25.50']);
        $sale = $this->createSale($oldCustomer, $oldSeller, '100.00');
        $existingItem = SaleItem::factory()->for($sale)->for($existingProduct)->create([
            'quantity' => 1,
            'unit_price' => '100.00',
            'total' => '100.00',
        ]);

        $response = $this->actingAs(User::factory()->create())->put(route('sales.update', $sale), [
            'customer_id' => $newCustomer->id,
            'seller_id' => $newSeller->id,
            'active' => '0',
            'items' => [
                [
                    'sale_item_id' => $existingItem->id,
                    'product_id' => $existingProduct->id,
                    'quantity' => 3,
                    'unit_price' => '0.01',
                    'total' => '0.03',
                ],
                [
                    'product_id' => $newProduct->id,
                    'quantity' => 2,
                    'unit_price' => '0.01',
                    'total' => '0.02',
                ],
            ],
            'total' => '0.05',
        ]);

        $response->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Venda atualizada com sucesso.')
            ->assertRedirect(route('sales.index'));
        $sale->refresh();
        $items = $sale->items()->get()->keyBy('product_id');
        $this->assertSame($newCustomer->id, $sale->customer_id);
        $this->assertSame($newSeller->id, $sale->seller_id);
        $this->assertFalse($sale->active);
        $this->assertSame('351.00', $sale->total);
        $this->assertSame('100.00', $items[$existingProduct->id]->unit_price);
        $this->assertSame('300.00', $items[$existingProduct->id]->total);
        $this->assertSame('25.50', $items[$newProduct->id]->unit_price);
        $this->assertSame('51.00', $items[$newProduct->id]->total);
    }

    public function test_historical_price_requires_the_original_sale_item_identity(): void
    {
        $product = Product::factory()->create(['sale_price' => '130.00']);
        $sale = $this->createSale(total: '100.00');
        SaleItem::factory()->for($sale)->for($product)->create([
            'quantity' => 1,
            'unit_price' => '100.00',
            'total' => '100.00',
        ]);

        $this->actingAs(User::factory()->create())->put(route('sales.update', $sale), [
            'customer_id' => $sale->customer_id,
            'seller_id' => $sale->seller_id,
            'active' => '1',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ])->assertSessionHasNoErrors();

        $updatedItem = $sale->items()->firstOrFail();
        $this->assertSame('130.00', $updatedItem->unit_price);
        $this->assertSame('260.00', $updatedItem->total);
        $this->assertSame('260.00', $sale->fresh()->total);
    }

    public function test_sale_item_identity_cannot_be_used_to_transfer_a_historical_price_to_another_product(): void
    {
        $historicalProduct = Product::factory()->create(['sale_price' => '130.00']);
        $otherProduct = Product::factory()->create(['sale_price' => '250.00']);
        $sale = $this->createSale(total: '100.00');
        $historicalItem = SaleItem::factory()->for($sale)->for($historicalProduct)->create([
            'quantity' => 1,
            'unit_price' => '100.00',
            'total' => '100.00',
        ]);

        $this->actingAs(User::factory()->create())->put(route('sales.update', $sale), [
            'customer_id' => $sale->customer_id,
            'seller_id' => $sale->seller_id,
            'active' => '1',
            'items' => [[
                'sale_item_id' => $historicalItem->id,
                'product_id' => $otherProduct->id,
                'quantity' => 2,
            ]],
        ])->assertSessionHasErrors(['items.0.product_id']);

        $this->assertModelExists($historicalItem);
        $this->assertSame('100.00', $sale->fresh()->total);
        $this->assertDatabaseMissing('sale_items', [
            'sale_id' => $sale->id,
            'product_id' => $otherProduct->id,
        ]);
    }

    public function test_editing_sale_can_remove_an_item(): void
    {
        $keptProduct = Product::factory()->create();
        $removedProduct = Product::factory()->create();
        $sale = $this->createSale(total: '30.00');
        $keptItem = SaleItem::factory()->for($sale)->for($keptProduct)->create([
            'quantity' => 1,
            'unit_price' => '10.00',
            'total' => '10.00',
        ]);
        SaleItem::factory()->for($sale)->for($removedProduct)->create([
            'quantity' => 1,
            'unit_price' => '20.00',
            'total' => '20.00',
        ]);

        $this->actingAs(User::factory()->create())->put(route('sales.update', $sale), [
            'customer_id' => $sale->customer_id,
            'seller_id' => $sale->seller_id,
            'active' => '1',
            'items' => [[
                'sale_item_id' => $keptItem->id,
                'product_id' => $keptProduct->id,
                'quantity' => 2,
            ]],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('sale_items', 1);
        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id,
            'product_id' => $keptProduct->id,
            'quantity' => 2,
            'unit_price' => '10.00',
            'total' => '20.00',
        ]);
        $this->assertSame('20.00', $sale->fresh()->total);
    }

    public function test_sale_cannot_be_updated_without_items_and_existing_data_is_preserved(): void
    {
        $originalCustomer = Person::factory()->customer()->create();
        $newCustomer = Person::factory()->customer()->create();
        $seller = Person::factory()->seller()->create();
        $product = Product::factory()->create();
        $sale = $this->createSale($originalCustomer, $seller, '40.00');
        $item = SaleItem::factory()->for($sale)->for($product)->create([
            'quantity' => 2,
            'unit_price' => '20.00',
            'total' => '40.00',
        ]);

        $this->actingAs(User::factory()->create())->put(route('sales.update', $sale), [
            'customer_id' => $newCustomer->id,
            'seller_id' => $seller->id,
            'active' => '0',
            'items' => [],
        ])->assertSessionHasErrors(['items']);

        $sale->refresh();
        $this->assertSame($originalCustomer->id, $sale->customer_id);
        $this->assertTrue($sale->active);
        $this->assertSame('40.00', $sale->total);
        $this->assertModelExists($item);
    }

    public function test_sale_update_rejects_duplicate_products_without_changing_existing_items(): void
    {
        $product = Product::factory()->create();
        $sale = $this->createSale(total: '18.00');
        $item = SaleItem::factory()->for($sale)->for($product)->create([
            'quantity' => 1,
            'unit_price' => '18.00',
            'total' => '18.00',
        ]);

        $this->actingAs(User::factory()->create())->put(route('sales.update', $sale), [
            'customer_id' => $sale->customer_id,
            'seller_id' => $sale->seller_id,
            'active' => '1',
            'items' => [
                ['sale_item_id' => $item->id, 'product_id' => $product->id, 'quantity' => 1],
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ])->assertSessionHasErrors(['items.1.product_id']);

        $this->assertDatabaseCount('sale_items', 1);
        $this->assertSame(1, $item->fresh()->quantity);
        $this->assertSame('18.00', $sale->fresh()->total);
    }

    public function test_sale_update_is_rolled_back_when_item_persistence_fails(): void
    {
        $originalCustomer = Person::factory()->customer()->create();
        $newCustomer = Person::factory()->customer()->create();
        $seller = Person::factory()->seller()->create();
        $originalProduct = Product::factory()->create();
        $newProduct = Product::factory()->create(['sale_price' => '30.00']);
        $sale = $this->createSale($originalCustomer, $seller, '20.00');
        $originalItem = SaleItem::factory()->for($sale)->for($originalProduct)->create([
            'quantity' => 1,
            'unit_price' => '20.00',
            'total' => '20.00',
        ]);
        $eventName = 'eloquent.creating: '.SaleItem::class;

        Event::listen($eventName, static function (): void {
            throw new \RuntimeException('Falha simulada ao persistir item.');
        });

        try {
            app(SaleService::class)->update($sale->id, [
                'customer_id' => $newCustomer->id,
                'seller_id' => $seller->id,
                'active' => false,
                'items' => [['product_id' => $newProduct->id, 'quantity' => 2]],
            ]);

            $this->fail('A falha simulada deveria interromper a atualização.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Falha simulada ao persistir item.', $exception->getMessage());
        } finally {
            Event::forget($eventName);
        }

        $sale->refresh();
        $this->assertSame($originalCustomer->id, $sale->customer_id);
        $this->assertTrue($sale->active);
        $this->assertSame('20.00', $sale->total);
        $this->assertModelExists($originalItem);
        $this->assertDatabaseMissing('sale_items', [
            'sale_id' => $sale->id,
            'product_id' => $newProduct->id,
        ]);
    }

    public function test_validation_error_reopens_edit_form_with_sale_context_and_submitted_items(): void
    {
        $product = Product::factory()->create();
        $sale = $this->createSale(total: '15.00');
        $item = SaleItem::factory()->for($sale)->for($product)->create([
            'quantity' => 1,
            'unit_price' => '15.00',
            'total' => '15.00',
        ]);
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->followingRedirects()
            ->from(route('sales.index'))
            ->put(route('sales.update', $sale), [
                'customer_id' => $sale->customer_id,
                'seller_id' => $sale->seller_id,
                'active' => '1',
                'form_mode' => 'edit',
                'sale_id' => (string) $sale->id,
                'items' => [[
                    'sale_item_id' => $item->id,
                    'product_id' => $product->id,
                    'quantity' => 0,
                ]],
            ]);

        $response
            ->assertOk()
            ->assertViewHas('formSale', fn (?array $formSale): bool => $formSale !== null && $formSale['id'] === $sale->id)
            ->assertSee('A quantidade deve ser pelo menos 1.')
            ->assertSee('Salvar alterações');
    }

    public function test_editing_historical_sale_allows_current_inactive_relations_but_rejects_new_inactive_ones(): void
    {
        $customer = Person::factory()->customer()->create(['active' => false]);
        $seller = Person::factory()->seller()->create(['active' => false]);
        $product = Product::factory()->create(['active' => false]);
        $sale = $this->createSale($customer, $seller, '50.00');
        $historicalItem = SaleItem::factory()->for($sale)->for($product)->create([
            'quantity' => 1,
            'unit_price' => '50.00',
            'total' => '50.00',
        ]);

        $this->actingAs(User::factory()->create())->put(route('sales.update', $sale), [
            'customer_id' => $customer->id,
            'seller_id' => $seller->id,
            'active' => '1',
            'items' => [[
                'sale_item_id' => $historicalItem->id,
                'product_id' => $product->id,
                'quantity' => 2,
            ]],
        ])->assertSessionHasNoErrors();

        $this->assertSame('100.00', $sale->fresh()->total);

        $inactiveCustomer = Person::factory()->customer()->create(['active' => false]);
        $inactiveSeller = Person::factory()->seller()->create(['active' => false]);
        $inactiveProduct = Product::factory()->create(['active' => false]);
        $this->actingAs(User::factory()->create())->put(route('sales.update', $sale), [
            'customer_id' => $inactiveCustomer->id,
            'seller_id' => $inactiveSeller->id,
            'active' => '1',
            'items' => [['product_id' => $inactiveProduct->id, 'quantity' => 1]],
        ])->assertSessionHasErrors(['customer_id', 'seller_id', 'items.0.product_id']);
    }

    public function test_sale_list_hides_inactive_by_default_and_can_show_all_statuses(): void
    {
        $active = Sale::factory()->create(['active' => true]);
        $inactive = Sale::factory()->inactive()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('sales.index'))
            ->assertOk()
            ->assertSee($active->formattedNumber())
            ->assertDontSee($inactive->formattedNumber())
            ->assertSee('bg-emerald-50 text-emerald-700', false)
            ->assertViewHas('showInactive', false);

        $this->actingAs($user)->get(route('sales.index', ['show_inactive' => '1']))
            ->assertOk()
            ->assertSee($active->formattedNumber())
            ->assertSee($inactive->formattedNumber())
            ->assertSee('bg-emerald-50 text-emerald-700', false)
            ->assertSee('bg-rose-50 text-rose-700', false)
            ->assertSee('Ativo')
            ->assertSee('Inativo')
            ->assertSee('value="1" checked', false)
            ->assertViewHas('showInactive', true);
    }

    public function test_sale_search_works_together_with_inactive_filter(): void
    {
        $targetCustomer = Person::factory()->customer()->create(['name' => 'Cliente Inativo Pesquisado']);
        $ignoredCustomer = Person::factory()->customer()->create(['name' => 'Cliente Inativo Ignorado']);
        Sale::factory()->inactive()->create(['customer_id' => $targetCustomer->id]);
        Sale::factory()->inactive()->create(['customer_id' => $ignoredCustomer->id]);

        $response = $this->actingAs(User::factory()->create())->get(route('sales.index', [
            'search' => 'Pesquisado',
            'show_inactive' => '1',
        ]));

        $response->assertOk()
            ->assertSee('Inativo')
            ->assertViewHas('sales', function (LengthAwarePaginator $sales) use ($targetCustomer): bool {
                return $sales->count() === 1 && $sales->first()->customer_id === $targetCustomer->id;
            });
    }

    public function test_customer_and_seller_relationships_enforce_person_type(): void
    {
        $customer = Person::factory()->customer()->create();
        $seller = Person::factory()->seller()->create();

        $invalidCustomerSale = Sale::query()->create([
            'customer_id' => $seller->id,
            'seller_id' => $seller->id,
            'total' => '10.00',
        ]);
        $invalidSellerSale = Sale::query()->create([
            'customer_id' => $customer->id,
            'seller_id' => $customer->id,
            'total' => '10.00',
        ]);

        $this->assertNull($invalidCustomerSale->customer);
        $this->assertNull($invalidSellerSale->seller);
    }

    public function test_details_load_customer_seller_and_items(): void
    {
        $customer = Person::factory()->customer()->create(['name' => 'Cliente Detalhes']);
        $seller = Person::factory()->seller()->create(['name' => 'Vendedor Detalhes']);
        $product = Product::factory()->create([
            'name' => 'Camiseta Especial',
            'photo_path' => 'products/camiseta-especial.webp',
        ]);
        $sale = $this->createSale($customer, $seller, '179.80');
        $item = SaleItem::factory()->for($sale)->for($product)->create([
            'quantity' => 2,
            'unit_price' => '89.90',
            'total' => '179.80',
        ]);

        $response = $this->actingAs(User::factory()->create())->getJson(route('sales.show', $sale));

        $response->assertOk()
            ->assertJsonPath('id', $sale->id)
            ->assertJsonPath('number', $sale->formattedNumber())
            ->assertJsonPath('date', $sale->created_at->format('d/m/Y H:i'))
            ->assertJsonPath('customer.name', 'Cliente Detalhes')
            ->assertJsonPath('customer.id', $customer->id)
            ->assertJsonPath('seller.name', 'Vendedor Detalhes')
            ->assertJsonPath('seller.id', $seller->id)
            ->assertJsonPath('active', true)
            ->assertJsonPath('status', 'Ativo')
            ->assertJsonPath('total', 'R$ 179,80')
            ->assertJsonPath('items.0.sale_item_id', $item->id)
            ->assertJsonPath('items.0.sku', $product->sku)
            ->assertJsonPath('items.0.product', 'Camiseta Especial')
            ->assertJsonPath('items.0.photo_url', '/storage/products/camiseta-especial.webp')
            ->assertJsonPath('items.0.quantity', 2)
            ->assertJsonPath('items.0.unit_price_value', '89.90')
            ->assertJsonPath('items.0.unit_price', 'R$ 89,90')
            ->assertJsonPath('items.0.total', 'R$ 179,80');
    }

    public function test_historical_price_comes_from_sale_item_and_is_not_changed_with_product_price(): void
    {
        $product = Product::factory()->create(['sale_price' => '120.00']);
        $sale = $this->createSale(total: '100.00');
        SaleItem::factory()->for($sale)->for($product)->create([
            'quantity' => 1,
            'unit_price' => '100.00',
            'total' => '100.00',
        ]);

        $product->update(['sale_price' => '150.00']);

        $response = $this->actingAs(User::factory()->create())->getJson(route('sales.show', $sale));
        $response->assertOk()
            ->assertJsonPath('items.0.unit_price', 'R$ 100,00')
            ->assertJsonPath('items.0.total', 'R$ 100,00');
    }

    public function test_inactive_people_and_product_remain_visible_in_historical_sale(): void
    {
        $customer = Person::factory()->customer()->create(['name' => 'Cliente Inativo', 'active' => false]);
        $seller = Person::factory()->seller()->create(['name' => 'Vendedor Inativo', 'active' => false]);
        $product = Product::factory()->create(['name' => 'Produto Inativo Histórico', 'active' => false]);
        $sale = $this->createSale($customer, $seller, '75.00');
        SaleItem::factory()->for($sale)->for($product)->create([
            'quantity' => 1,
            'unit_price' => '75.00',
            'total' => '75.00',
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('sales.index'))
            ->assertOk()
            ->assertSee('Cliente Inativo')
            ->assertSee('Vendedor Inativo');
        $this->actingAs($user)->getJson(route('sales.show', $sale))
            ->assertOk()
            ->assertJsonPath('items.0.product', 'Produto Inativo Histórico');
    }

    public function test_deactivation_preserves_sale_and_all_historical_relations(): void
    {
        $customer = Person::factory()->customer()->create(['name' => 'Cliente histórico']);
        $seller = Person::factory()->seller()->create(['name' => 'Vendedor histórico']);
        $product = Product::factory()->create(['name' => 'Produto histórico']);
        $sale = $this->createSale($customer, $seller, '50.00');
        $item = SaleItem::factory()->for($sale)->for($product)->create([
            'quantity' => 1,
            'unit_price' => '50.00',
            'total' => '50.00',
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('sales.index'))
            ->assertSee(route('sales.deactivate', $sale), false)
            ->assertSee('Excluir venda?');

        $this->actingAs($user)->patch(route('customers.deactivate', $customer));
        $this->actingAs($user)->patch(route('sellers.deactivate', $seller));
        $this->actingAs($user)->patch(route('products.deactivate', $product));
        $this->actingAs($user)
            ->patch(route('sales.deactivate', $sale))
            ->assertRedirect(route('sales.index'))
            ->assertSessionHas('success', 'Venda inativada com sucesso.');

        $this->assertModelExists($customer);
        $this->assertModelExists($seller);
        $this->assertModelExists($product);
        $this->assertModelExists($sale);
        $this->assertModelExists($item);
        $this->assertFalse($sale->fresh()->active);

        $this->actingAs($user)->get(route('sales.index'))
            ->assertDontSee($sale->formattedNumber());
        $this->actingAs($user)->get(route('sales.index', ['show_inactive' => '1']))
            ->assertSee($sale->formattedNumber())
            ->assertSee($customer->name)
            ->assertSee($seller->name)
            ->assertDontSee(route('sales.deactivate', $sale), false);
        $this->actingAs($user)->getJson(route('sales.show', $sale))
            ->assertOk()
            ->assertJsonPath('customer.name', $customer->name)
            ->assertJsonPath('seller.name', $seller->name)
            ->assertJsonPath('items.0.product', $product->name);
    }

    public function test_list_calculates_total_quantity_and_formats_total(): void
    {
        $sale = $this->createSale(total: '339.70');
        SaleItem::factory()->for($sale)->create([
            'quantity' => 2,
            'unit_price' => '89.90',
            'total' => '179.80',
        ]);
        SaleItem::factory()->for($sale)->create([
            'quantity' => 1,
            'unit_price' => '159.90',
            'total' => '159.90',
        ]);

        $response = $this->actingAs(User::factory()->create())->get(route('sales.index'));

        $response->assertOk()
            ->assertSee($sale->formattedNumber())
            ->assertSee('>3</td>', false)
            ->assertSee('R$ 339,70');
    }

    public function test_search_finds_sale_by_formatted_number(): void
    {
        $target = $this->createSale();
        $other = $this->createSale();

        $response = $this->actingAs(User::factory()->create())->get(route('sales.index', [
            'search' => $target->formattedNumber(),
        ]));

        $response->assertOk()
            ->assertSee($target->formattedNumber())
            ->assertDontSee($other->formattedNumber());
    }

    public function test_search_finds_sale_by_customer_name(): void
    {
        $targetCustomer = Person::factory()->customer()->create(['name' => 'Cliente Venda Encontrado']);
        $otherCustomer = Person::factory()->customer()->create(['name' => 'Cliente Venda Ignorado']);
        $this->createSale($targetCustomer);
        $this->createSale($otherCustomer);

        $response = $this->actingAs(User::factory()->create())->get(route('sales.index', ['search' => 'Encontrado']));

        $response->assertOk()->assertViewHas('sales', function (LengthAwarePaginator $sales) use ($targetCustomer): bool {
            return $sales->count() === 1 && $sales->first()->customer_id === $targetCustomer->id;
        });
    }

    public function test_search_finds_sale_by_seller_name(): void
    {
        $targetSeller = Person::factory()->seller()->create(['name' => 'Vendedor Venda Encontrado']);
        $otherSeller = Person::factory()->seller()->create(['name' => 'Vendedor Venda Ignorado']);
        $this->createSale(seller: $targetSeller);
        $this->createSale(seller: $otherSeller);

        $response = $this->actingAs(User::factory()->create())->get(route('sales.index', ['search' => 'Encontrado']));

        $response->assertOk()->assertViewHas('sales', function (LengthAwarePaginator $sales) use ($targetSeller): bool {
            return $sales->count() === 1 && $sales->first()->seller_id === $targetSeller->id;
        });
    }

    public function test_sale_list_is_paginated_with_five_records_and_preserves_search(): void
    {
        $customer = Person::factory()->customer()->create(['name' => 'Cliente Paginação']);
        $seller = Person::factory()->seller()->create();
        Sale::factory()->count(6)->create([
            'customer_id' => $customer->id,
            'seller_id' => $seller->id,
        ]);

        $response = $this->actingAs(User::factory()->create())->get(route('sales.index', [
            'search' => 'Paginação',
            'show_inactive' => '1',
        ]));

        $response->assertOk()
            ->assertSee('Exibindo 1 a 5 de 6 registros')
            ->assertViewHas('sales', function (LengthAwarePaginator $sales): bool {
                return $sales->perPage() === 5
                    && $sales->count() === 5
                    && $sales->total() === 6
                    && str_contains((string) $sales->nextPageUrl(), 'search=Pagina%C3%A7%C3%A3o')
                    && str_contains((string) $sales->nextPageUrl(), 'show_inactive=1');
            });
    }

    public function test_sales_have_store_and_update_routes_but_no_delete_route(): void
    {
        $this->assertTrue(Route::has('sales.store'));
        $this->assertTrue(Route::has('sales.update'));
        $this->assertTrue(Route::has('sales.deactivate'));
        $this->assertFalse(Route::has('sales.destroy'));

        $user = User::factory()->create();
        $this->actingAs($user)->delete('/sales/1')->assertMethodNotAllowed();
    }

    /** @return array<string, mixed> */
    private function validSalePayload(): array
    {
        return [
            'customer_id' => Person::factory()->customer()->create()->id,
            'seller_id' => Person::factory()->seller()->create()->id,
            'items' => [[
                'product_id' => Product::factory()->create()->id,
                'quantity' => 1,
            ]],
        ];
    }

    private function createSale(
        ?Person $customer = null,
        ?Person $seller = null,
        string $total = '10.00',
    ): Sale {
        return Sale::query()->create([
            'customer_id' => ($customer ?? Person::factory()->customer()->create())->id,
            'seller_id' => ($seller ?? Person::factory()->seller()->create())->id,
            'total' => $total,
        ]);
    }
}
