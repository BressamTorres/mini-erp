<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use App\Services\BusinessContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitor_cannot_deactivate_product(): void
    {
        $product = Product::factory()->create();

        $this->patch(route('products.deactivate', $product))
            ->assertRedirect(route('login'));

        $this->assertTrue($product->fresh()->active);
    }

    public function test_visitor_cannot_access_products(): void
    {
        $this->get(route('products.index'))->assertRedirect(route('login'));
    }

    public function test_visitor_cannot_change_products(): void
    {
        $product = Product::factory()->create();

        $this->post(route('products.store'), $this->validPayload())->assertRedirect(route('login'));
        $this->put(route('products.update', $product), $this->validPayload())->assertRedirect(route('login'));

        $this->assertDatabaseCount('products', 1);
        $this->assertModelExists($product);
    }

    public function test_authenticated_user_can_access_product_list(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('products.index'));

        $response->assertOk()
            ->assertSee('Cadastre e gerencie seus produtos.')
            ->assertSee('Novo produto')
            ->assertSee('Imagem do produto')
            ->assertSee('Preço de venda')
            ->assertSee('Nenhum produto encontrado')
            ->assertSee('colspan="5"', false)
            ->assertSee('role="combobox"', false)
            ->assertSee('name="active"', false)
            ->assertSee('000001')
            ->assertSee('Código previsto. Confirmado ao salvar.')
            ->assertSee('Gerado automaticamente após salvar')
            ->assertSee('id="product-sku" type="text" disabled readonly', false)
            ->assertDontSee('name="sku"', false)
            ->assertSee('@change="$el.form.requestSubmit()"', false)
            ->assertDontSee('<select id="product-active"', false)
            ->assertSee('enctype="multipart/form-data"', false);
    }

    public function test_sku_preview_does_not_reserve_or_consume_product_ids(): void
    {
        $product = Product::factory()->create();
        $product->delete();
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('products.index'))->assertSee('000002');
        $this->actingAs($user)->get(route('products.index'))->assertSee('000002');

        $this->actingAs($user)
            ->post(route('products.store'), $this->validPayload())
            ->assertSessionHasNoErrors();

        $createdProduct = Product::query()->sole();
        $this->assertSame(2, $createdProduct->id);
        $this->assertSame('000002', $createdProduct->sku);
    }

    public function test_authenticated_user_can_create_product_without_photo(): void
    {
        $response = $this->actingAs(User::factory()->create())->post(route('products.store'), [
            'name' => 'Camiseta Básica',
            'sale_price' => 'R$ 1.234,56',
            'active' => '1',
        ]);

        $response->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Produto cadastrado com sucesso.')
            ->assertRedirect(route('products.index'));
        $product = Product::query()->firstOrFail();
        $this->assertSame(Product::skuFromId($product->id), $product->sku);
        $this->assertSame('1234.56', $product->sale_price);
        $this->assertTrue($product->active);
        $this->assertNull($product->photo_path);
    }

    public function test_new_product_uses_active_database_default(): void
    {
        $product = Product::query()->create([
            'name' => 'Produto padrão',
            'sale_price' => '10.00',
        ]);

        $this->assertTrue($product->refresh()->active);
        $this->assertSame(Product::skuFromId($product->id), $product->sku);
    }

    public function test_authenticated_user_can_create_active_product(): void
    {
        $payload = $this->validPayload();
        $payload['active'] = '1';

        $user = User::factory()->create();
        $response = $this->actingAs($user)->post(route('products.store'), $payload);

        $response->assertSessionHasNoErrors();
        $product = Product::query()->firstOrFail();
        $this->assertIsBool($product->active);
        $this->assertTrue($product->active);
    }

    public function test_product_creation_ignores_manipulated_inactive_value(): void
    {
        $payload = $this->validPayload();
        $payload['active'] = '0';

        $user = User::factory()->create();
        $response = $this->actingAs($user)->post(route('products.store'), $payload);

        $response->assertSessionHasNoErrors();
        $product = Product::query()->firstOrFail();
        $this->assertIsBool($product->active);
        $this->assertTrue($product->active);
    }

    public function test_active_is_not_required_when_creating_product(): void
    {
        $payload = $this->validPayload();
        unset($payload['active']);

        $response = $this->actingAs(User::factory()->create())
            ->from(route('products.index'))
            ->post(route('products.store'), $payload);

        $response->assertSessionHasNoErrors();
        $this->assertTrue(Product::query()->firstOrFail()->active);
    }

    public function test_sku_is_generated_from_database_id_with_at_least_six_digits(): void
    {
        DB::table('products')->insert([
            'id' => 124,
            'business_id' => app(BusinessContext::class)->id(),
            'sku' => 'SKU-LEGADO',
            'name' => 'Produto legado',
            'sale_price' => '10.00',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs(User::factory()->create())
            ->post(route('products.store'), $this->validPayload())
            ->assertSessionHasNoErrors();

        $product = Product::query()->where('name', 'Produto Teste')->sole();
        $this->assertSame(125, $product->id);
        $this->assertSame('000125', $product->sku);
        $this->assertSame('1000000', Product::skuFromId(1_000_000));
    }

    public function test_product_creation_rejects_manually_supplied_sku(): void
    {
        $payload = $this->validPayload();
        $payload['sku'] = 'MANUAL001';

        $response = $this->actingAs(User::factory()->create())
            ->from(route('products.index'))
            ->post(route('products.store'), $payload);

        $response->assertSessionHasErrors(['sku' => 'O SKU é gerado automaticamente e não pode ser informado.']);
        $this->assertDatabaseEmpty('products');
    }

    public function test_eloquent_creation_ignores_manually_supplied_sku(): void
    {
        $product = Product::query()->create([
            'sku' => 'MANUAL001',
            'name' => 'Produto automático',
            'sale_price' => '10.00',
        ]);

        $this->assertSame(Product::skuFromId($product->id), $product->sku);
        $this->assertNotSame('MANUAL001', $product->sku);
    }

    public function test_name_is_required(): void
    {
        $payload = $this->validPayload();
        unset($payload['name']);

        $response = $this->actingAs(User::factory()->create())
            ->from(route('products.index'))
            ->post(route('products.store'), $payload);

        $response->assertSessionHasErrors(['name' => 'O nome é obrigatório.']);
        $this->assertDatabaseEmpty('products');
    }

    public function test_sale_price_is_required(): void
    {
        $payload = $this->validPayload();
        unset($payload['sale_price']);

        $response = $this->actingAs(User::factory()->create())
            ->from(route('products.index'))
            ->post(route('products.store'), $payload);

        $response->assertSessionHasErrors(['sale_price' => 'O preço de venda é obrigatório.']);
        $this->assertDatabaseEmpty('products');
    }

    public function test_sale_price_must_be_greater_than_zero(): void
    {
        $payload = $this->validPayload();
        $payload['sale_price'] = 'R$ 0,00';

        $response = $this->actingAs(User::factory()->create())
            ->from(route('products.index'))
            ->post(route('products.store'), $payload);

        $response->assertSessionHasErrors(['sale_price' => 'O preço de venda deve ser maior que zero.']);
        $this->assertDatabaseEmpty('products');
    }

    public function test_invalid_sale_price_is_rejected(): void
    {
        $payload = $this->validPayload();
        $payload['sale_price'] = 'valor inválido';

        $response = $this->actingAs(User::factory()->create())
            ->from(route('products.index'))
            ->post(route('products.store'), $payload);

        $response->assertSessionHasErrors(['sale_price' => 'Informe um preço de venda válido.']);
        $this->assertDatabaseEmpty('products');
    }

    #[DataProvider('validPhotoSizes')]
    public function test_product_can_be_created_with_photo_up_to_five_megabytes(int $size): void
    {
        Storage::fake('public');
        $payload = $this->validPayload();
        $payload['photo'] = $this->fakeImage('produto.jpg')->size($size);

        $user = User::factory()->create();
        $response = $this->actingAs($user)->post(route('products.store'), $payload);

        $response->assertSessionHasNoErrors();
        $product = Product::query()->firstOrFail();
        $this->assertNotNull($product->photo_path);
        $this->assertStringStartsWith("businesses/{$user->business_id}/products/", $product->photo_path);
        $this->assertNotSame('products/produto.jpg', $product->photo_path);
        Storage::disk('public')->assertExists($product->photo_path);
    }

    public function test_invalid_photo_format_is_rejected(): void
    {
        Storage::fake('public');
        $payload = $this->validPayload();
        $payload['photo'] = UploadedFile::fake()->create('arquivo.pdf', 100, 'application/pdf');

        $response = $this->actingAs(User::factory()->create())
            ->from(route('products.index'))
            ->post(route('products.store'), $payload);

        $response->assertSessionHasErrors(['photo' => 'A foto deve estar no formato JPG, JPEG, PNG ou WEBP.']);
        $this->assertDatabaseEmpty('products');
    }

    public function test_photo_larger_than_five_megabytes_is_rejected(): void
    {
        Storage::fake('public');
        $payload = $this->validPayload();
        $payload['photo'] = $this->fakeImage('produto.jpg')->size(5121);

        $response = $this->actingAs(User::factory()->create())
            ->from(route('products.index'))
            ->post(route('products.store'), $payload);

        $response
            ->assertSessionHasErrors(['photo' => 'A imagem deve ter no máximo 5 MB. Selecione um arquivo menor.'])
            ->assertSessionHasInput('name', $payload['name'])
            ->assertRedirect(route('products.index'));
        $this->assertDatabaseEmpty('products');
    }

    public function test_authenticated_user_can_update_product(): void
    {
        $product = Product::factory()->create();
        $originalSku = $product->sku;

        $response = $this->actingAs(User::factory()->create())->put(route('products.update', $product), [
            'name' => 'Produto atualizado',
            'sale_price' => 'R$ 259,90',
            'active' => '1',
        ]);

        $response->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Produto atualizado com sucesso.')
            ->assertRedirect(route('products.index'));
        $product->refresh();
        $this->assertSame($originalSku, $product->sku);
        $this->assertSame('Produto atualizado', $product->name);
        $this->assertSame('259.90', $product->sale_price);
    }

    public function test_authenticated_user_can_update_active_product_to_inactive(): void
    {
        $product = Product::factory()->create(['active' => true]);
        $payload = $this->validPayload();
        $payload['active'] = '0';

        $response = $this->actingAs(User::factory()->create())->put(route('products.update', $product), $payload);

        $response->assertSessionHasNoErrors();
        $this->assertFalse($product->fresh()->active);
    }

    public function test_authenticated_user_can_update_inactive_product_to_active(): void
    {
        $product = Product::factory()->create(['active' => false]);
        $payload = $this->validPayload();
        $payload['active'] = '1';

        $response = $this->actingAs(User::factory()->create())->put(route('products.update', $product), $payload);

        $response->assertSessionHasNoErrors();
        $this->assertTrue($product->fresh()->active);
    }

    public function test_product_can_be_updated_keeping_its_current_sku(): void
    {
        $product = Product::factory()->create();
        $originalSku = $product->sku;
        $payload = $this->validPayload();

        $response = $this->actingAs(User::factory()->create())->put(route('products.update', $product), $payload);

        $response->assertSessionHasNoErrors();
        $this->assertSame($originalSku, $product->fresh()->sku);
    }

    public function test_product_update_rejects_manually_supplied_sku(): void
    {
        $product = Product::factory()->create(['name' => 'Produto original']);
        $originalSku = $product->sku;
        $payload = $this->validPayload();
        $payload['sku'] = 'MANUAL002';
        $payload['name'] = 'Produto alterado';

        $response = $this->actingAs(User::factory()->create())
            ->from(route('products.index'))
            ->put(route('products.update', $product), $payload);

        $response->assertSessionHasErrors(['sku' => 'O SKU é gerado automaticamente e não pode ser informado.']);
        $product->refresh();
        $this->assertSame($originalSku, $product->sku);
        $this->assertSame('Produto original', $product->name);
    }

    public function test_product_model_prevents_sku_changes(): void
    {
        $product = Product::factory()->create();
        $product->sku = 'MANUAL003';

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('O SKU do produto não pode ser alterado.');

        $product->save();
    }

    public function test_existing_legacy_sku_is_preserved_when_product_is_updated(): void
    {
        DB::table('products')->insert([
            'business_id' => app(BusinessContext::class)->id(),
            'sku' => 'SKU-LEGADO-001',
            'name' => 'Produto legado',
            'sale_price' => '10.00',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $product = Product::query()->where('sku', 'SKU-LEGADO-001')->sole();
        $payload = $this->validPayload();
        $payload['name'] = 'Produto legado atualizado';

        $this->actingAs(User::factory()->create())
            ->put(route('products.update', $product), $payload)
            ->assertSessionHasNoErrors();

        $this->assertSame('SKU-LEGADO-001', $product->fresh()->sku);
    }

    public function test_update_without_new_photo_keeps_current_photo(): void
    {
        Storage::fake('public');
        $path = 'products/current.jpg';
        Storage::disk('public')->put($path, 'content');
        $product = Product::factory()->create(['photo_path' => $path]);

        $response = $this->actingAs(User::factory()->create())->put(route('products.update', $product), $this->validPayload());

        $response->assertSessionHasNoErrors();
        $this->assertSame($path, $product->fresh()->photo_path);
        Storage::disk('public')->assertExists($path);
    }

    #[DataProvider('validPhotoSizes')]
    public function test_replacing_photo_up_to_five_megabytes_updates_path_and_removes_old_file(int $size): void
    {
        Storage::fake('public');
        $oldPath = 'products/old.jpg';
        Storage::disk('public')->put($oldPath, 'old');
        $product = Product::factory()->create(['photo_path' => $oldPath]);
        $payload = $this->validPayload();
        $payload['photo'] = $this->fakeImage('new.png')->size($size);

        $response = $this->actingAs(User::factory()->create())->put(route('products.update', $product), $payload);

        $response->assertSessionHasNoErrors();
        $newPath = $product->fresh()->photo_path;
        $this->assertNotNull($newPath);
        $this->assertNotSame($oldPath, $newPath);
        Storage::disk('public')->assertExists($newPath);
        Storage::disk('public')->assertMissing($oldPath);
    }

    public function test_update_rejects_photo_larger_than_five_megabytes_and_keeps_current_data(): void
    {
        Storage::fake('public');
        $oldPath = 'products/current.jpg';
        Storage::disk('public')->put($oldPath, 'current');
        $product = Product::factory()->create([
            'name' => 'Produto original',
            'photo_path' => $oldPath,
        ]);
        $payload = $this->validPayload();
        $payload['name'] = 'Produto preenchido';
        $payload['photo'] = $this->fakeImage('oversized.jpg')->size(5121);

        $response = $this->actingAs(User::factory()->create())
            ->from(route('products.index'))
            ->put(route('products.update', $product), $payload);

        $response
            ->assertSessionHasErrors(['photo' => 'A imagem deve ter no máximo 5 MB. Selecione um arquivo menor.'])
            ->assertSessionHasInput('name', 'Produto preenchido')
            ->assertRedirect(route('products.index'));
        $this->assertSame('Produto original', $product->fresh()->name);
        $this->assertSame($oldPath, $product->fresh()->photo_path);
        Storage::disk('public')->assertExists($oldPath);
    }

    public function test_authenticated_user_can_remove_photo(): void
    {
        Storage::fake('public');
        $path = 'products/remove.webp';
        Storage::disk('public')->put($path, 'content');
        $product = Product::factory()->create(['photo_path' => $path]);
        $payload = $this->validPayload();
        $payload['remove_photo'] = '1';

        $response = $this->actingAs(User::factory()->create())->put(route('products.update', $product), $payload);

        $response->assertSessionHasNoErrors();
        $this->assertNull($product->fresh()->photo_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_inactivating_product_keeps_record_and_photo(): void
    {
        Storage::fake('public');
        $path = 'products/status.jpg';
        Storage::disk('public')->put($path, 'content');
        $product = Product::factory()->create(['active' => true, 'photo_path' => $path]);
        $payload = $this->validPayload();
        $payload['active'] = '0';

        $response = $this->actingAs(User::factory()->create())->put(route('products.update', $product), $payload);

        $response->assertSessionHasNoErrors();
        $this->assertModelExists($product);
        $this->assertFalse($product->fresh()->active);
        $this->assertSame($path, $product->fresh()->photo_path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_product_delete_route_does_not_exist(): void
    {
        $this->assertFalse(Route::has('products.destroy'));
        $this->assertTrue(Route::has('products.deactivate'));

        $product = Product::factory()->create();
        $this->actingAs(User::factory()->create())
            ->delete("/products/{$product->id}")
            ->assertMethodNotAllowed();
    }

    public function test_authenticated_user_can_deactivate_product_without_deleting_it(): void
    {
        $product = Product::factory()->create(['name' => 'Produto para inativar']);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('products.index'))
            ->assertSee(route('products.deactivate', $product), false)
            ->assertSee('Excluir produto?');

        $this->actingAs($user)
            ->patch(route('products.deactivate', $product))
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('success', 'Produto inativado com sucesso.');

        $this->assertModelExists($product);
        $this->assertFalse($product->fresh()->active);

        $this->actingAs($user)->get(route('products.index'))
            ->assertDontSee($product->name);
        $this->actingAs($user)->get(route('products.index', ['show_inactive' => '1']))
            ->assertSee($product->name)
            ->assertDontSee(route('products.deactivate', $product), false);
    }

    public function test_search_finds_product_by_partial_sku(): void
    {
        $target = Product::factory()->create(['name' => 'Produto encontrado']);
        Product::factory()->create(['name' => 'Produto ignorado']);

        $response = $this->actingAs(User::factory()->create())->get(route('products.index', ['search' => $target->sku]));

        $response->assertOk()->assertSee('Produto encontrado')->assertDontSee('Produto ignorado');
    }

    public function test_search_finds_product_by_partial_name(): void
    {
        Product::factory()->create(['name' => 'Camiseta Básica']);
        Product::factory()->create(['name' => 'Tênis Casual']);

        $response = $this->actingAs(User::factory()->create())->get(route('products.index', ['search' => 'Básica']));

        $response->assertOk()->assertSee('Camiseta Básica')->assertDontSee('Tênis Casual');
    }

    public function test_list_formats_sale_price(): void
    {
        Product::factory()->create(['sale_price' => '1234.50']);

        $response = $this->actingAs(User::factory()->create())->get(route('products.index'));

        $response->assertOk()->assertSee('R$ 1.234,50');
    }

    public function test_product_list_hides_inactive_by_default_and_can_show_all_statuses(): void
    {
        $active = Product::factory()->create(['name' => 'Produto ativo', 'active' => true]);
        $inactive = Product::factory()->create(['name' => 'Produto inativo oculto', 'active' => false]);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('products.index'))
            ->assertOk()
            ->assertSee($active->name)
            ->assertDontSee($inactive->name)
            ->assertViewHas('showInactive', false);

        $this->actingAs($user)->get(route('products.index', ['show_inactive' => '1']))
            ->assertOk()
            ->assertSee($active->name)
            ->assertSee($inactive->name)
            ->assertViewHas('showInactive', true)
            ->assertSee('value="1" checked', false);
    }

    public function test_product_search_works_together_with_inactive_filter(): void
    {
        $target = Product::factory()->create(['name' => 'Camiseta inativa filtrada', 'active' => false]);
        Product::factory()->create(['name' => 'Tênis inativo', 'active' => false]);

        $response = $this->actingAs(User::factory()->create())->get(route('products.index', [
            'search' => 'Camiseta',
            'show_inactive' => '1',
        ]));

        $response->assertOk()->assertSee($target->name)->assertDontSee('Tênis inativo');
    }

    public function test_product_list_is_paginated_with_five_records_and_preserves_search(): void
    {
        Product::factory()->count(11)->sequence(fn (Sequence $sequence): array => [
            'name' => sprintf('Produto %02d', $sequence->index + 1),
        ])->create();

        $response = $this->actingAs(User::factory()->create())->get(route('products.index', [
            'search' => 'Produto',
            'show_inactive' => '1',
        ]));

        $response->assertOk()
            ->assertSee('Produto 01')
            ->assertSee('Produto 05')
            ->assertDontSee('Produto 06')
            ->assertSee('Exibindo 1 a 5 de 11 registros')
            ->assertViewHas('products', function (LengthAwarePaginator $products): bool {
                return $products->perPage() === 5
                    && $products->count() === 5
                    && $products->total() === 11
                    && str_contains((string) $products->nextPageUrl(), 'search=Produto')
                    && str_contains((string) $products->nextPageUrl(), 'show_inactive=1');
            });
    }

    /** @return array<string, array{int}> */
    public static function validPhotoSizes(): array
    {
        return [
            'four megabytes' => [4096],
            'exactly five megabytes' => [5120],
        ];
    }

    /**
     * @return array{name: string, sale_price: string, active: string}
     */
    private function validPayload(): array
    {
        return [
            'name' => 'Produto Teste',
            'sale_price' => 'R$ 129,90',
            'active' => '1',
        ];
    }
}
