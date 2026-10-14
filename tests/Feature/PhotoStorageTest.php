<?php

namespace Tests\Feature;

use App\Models\Person;
use App\Models\Product;
use App\Models\User;
use App\Services\PersonService;
use App\Services\ProductService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhotoStorageTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_photo_upload_uses_configured_private_disk(): void
    {
        config(['filesystems.person_photo_disk' => 'r2_private']);
        Storage::fake('r2_private');

        $this->actingAs(User::factory()->create())->post(route('customers.store'), [
            'name' => 'Cliente Local',
            'phone' => '(44) 99999-9999',
            'photo' => $this->fakeImage('cliente.png'),
        ])->assertSessionHasNoErrors();

        $customer = Person::query()->where('type', Person::TYPE_CUSTOMER)->firstOrFail();

        $this->assertNotNull($customer->photo_path);
        $this->assertSame('r2_private', $customer->photo_disk);
        Storage::disk('r2_private')->assertExists($customer->photo_path);
    }

    public function test_seller_photo_upload_uses_configured_private_disk(): void
    {
        config(['filesystems.person_photo_disk' => 'r2_private']);
        Storage::fake('r2_private');

        $this->actingAs(User::factory()->create())->post(route('sellers.store'), [
            'name' => 'Vendedor Privado',
            'phone' => '(44) 99999-9999',
            'photo' => $this->fakeImage('vendedor.webp'),
        ])->assertSessionHasNoErrors();

        $seller = Person::query()->where('type', Person::TYPE_SELLER)->firstOrFail();

        $this->assertNotNull($seller->photo_path);
        $this->assertSame('r2_private', $seller->photo_disk);
        Storage::disk('r2_private')->assertExists($seller->photo_path);
    }

    public function test_product_photo_upload_uses_configured_products_disk(): void
    {
        config(['filesystems.product_photo_disk' => 'r2_products']);
        Storage::fake('r2_products');

        $this->actingAs(User::factory()->create())->post(route('products.store'), [
            'sku' => 'R2-001',
            'name' => 'Produto R2',
            'sale_price' => '149,90',
            'photo' => $this->fakeImage('produto.webp'),
        ])->assertSessionHasNoErrors();

        $product = Product::query()->firstOrFail();

        $this->assertNotNull($product->photo_path);
        $this->assertSame('r2_products', $product->photo_disk);
        Storage::disk('r2_products')->assertExists($product->photo_path);
        Storage::disk('public')->assertMissing($product->photo_path);
    }

    public function test_legacy_local_product_photo_keeps_working_without_photo_disk(): void
    {
        config([
            'app.url' => 'https://erp.example.test',
            'filesystems.legacy_photo_disk' => 'public',
            'filesystems.disks.public.url' => '/storage',
        ]);
        Storage::forgetDisk('public');

        $product = new Product(['photo_path' => 'products/produto.webp']);

        $this->assertSame('/storage/products/produto.webp', $product->photoUrl());
    }

    public function test_product_photo_url_uses_public_products_domain_instead_of_app_url(): void
    {
        config([
            'app.url' => 'https://erp.example.test',
            'filesystems.disks.r2_products' => [
                'driver' => 's3',
                'key' => 'test-key',
                'secret' => 'test-secret',
                'region' => 'auto',
                'bucket' => 'mini-erp-images',
                'url' => 'https://media.example.test',
                'endpoint' => 'https://account-id.r2.cloudflarestorage.com',
                'use_path_style_endpoint' => false,
                'throw' => false,
            ],
        ]);
        Storage::forgetDisk('r2_products');

        $product = new Product([
            'photo_path' => 'products/produto.webp',
            'photo_disk' => 'r2_products',
        ]);

        $this->assertSame('https://media.example.test/products/produto.webp', $product->photoUrl());
        $this->assertStringNotContainsString('erp.example.test', $product->photoUrl());
    }

    public function test_private_person_photo_requires_authentication_and_is_streamed_by_erp(): void
    {
        Storage::fake('r2_private');
        $path = $this->fakeImage('cliente.png')->store('persons', 'r2_private');
        $customer = Person::factory()->customer()->create([
            'photo_path' => $path,
            'photo_disk' => 'r2_private',
        ]);

        $this->get(route('persons.photo', $customer))
            ->assertRedirect(route('login'));

        $this->assertSame(route('persons.photo', $customer), $customer->photoUrl());
        $this->assertStringNotContainsString('r2.cloudflarestorage.com', $customer->photoUrl());
        $this->assertArrayNotHasKey('url', config('filesystems.disks.r2_private'));

        $this->actingAs(User::factory()->create())
            ->get(route('persons.photo', $customer))
            ->assertOk()
            ->assertHeader('Cache-Control', 'max-age=300, private')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_replacing_legacy_person_photo_moves_storage_to_private_disk(): void
    {
        config([
            'filesystems.legacy_photo_disk' => 'public',
            'filesystems.person_photo_disk' => 'r2_private',
        ]);
        Storage::fake('public');
        Storage::fake('r2_private');
        Storage::disk('public')->put('persons/legacy.jpg', 'legacy-photo');
        $customer = Person::factory()->customer()->create([
            'photo_path' => 'persons/legacy.jpg',
            'photo_disk' => null,
        ]);

        $this->actingAs(User::factory()->create())->put(route('customers.update', $customer), [
            'name' => $customer->name,
            'phone' => $customer->phone,
            'active' => '1',
            'photo' => $this->fakeImage('nova.jpg'),
        ])->assertSessionHasNoErrors();

        $customer->refresh();

        $this->assertSame('r2_private', $customer->photo_disk);
        Storage::disk('public')->assertMissing('persons/legacy.jpg');
        Storage::disk('r2_private')->assertExists($customer->photo_path);
    }

    public function test_replacing_legacy_product_photo_moves_storage_to_products_disk(): void
    {
        config([
            'filesystems.legacy_photo_disk' => 'public',
            'filesystems.product_photo_disk' => 'r2_products',
        ]);
        Storage::fake('public');
        Storage::fake('r2_products');
        Storage::disk('public')->put('products/legacy.jpg', 'legacy-photo');
        $product = Product::factory()->create([
            'photo_path' => 'products/legacy.jpg',
            'photo_disk' => null,
        ]);

        $this->actingAs(User::factory()->create())->put(route('products.update', $product), [
            'sku' => $product->sku,
            'name' => $product->name,
            'sale_price' => $product->sale_price,
            'active' => '1',
            'photo' => $this->fakeImage('novo.jpg'),
        ])->assertSessionHasNoErrors();

        $product->refresh();

        $this->assertSame('r2_products', $product->photo_disk);
        Storage::disk('public')->assertMissing('products/legacy.jpg');
        Storage::disk('r2_products')->assertExists($product->photo_path);
    }

    public function test_legacy_local_person_photo_is_served_by_authenticated_route(): void
    {
        config(['filesystems.legacy_photo_disk' => 'public']);
        Storage::fake('public');
        $path = $this->fakeImage('cliente-antigo.jpg')->store('persons', 'public');
        $customer = Person::factory()->customer()->create([
            'photo_path' => $path,
            'photo_disk' => null,
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('persons.photo', $customer))
            ->assertOk();
    }

    public function test_shared_photo_is_only_deleted_after_last_reference_is_removed(): void
    {
        config([
            'filesystems.legacy_photo_disk' => 'public',
            'filesystems.person_photo_disk' => 'public',
            'filesystems.product_photo_disk' => 'public',
        ]);
        Storage::fake('public');
        Storage::disk('public')->put('shared/photo.jpg', 'shared-photo');
        $customer = Person::factory()->customer()->create([
            'photo_path' => 'shared/photo.jpg',
            'photo_disk' => 'public',
        ]);
        $product = Product::factory()->create([
            'photo_path' => 'shared/photo.jpg',
            'photo_disk' => 'public',
        ]);

        app(ProductService::class)->update($product->id, [
            'sku' => $product->sku,
            'name' => $product->name,
            'sale_price' => $product->sale_price,
            'active' => true,
            'remove_photo' => true,
        ]);

        Storage::disk('public')->assertExists('shared/photo.jpg');

        app(PersonService::class)->update(Person::TYPE_CUSTOMER, $customer->id, [
            'name' => $customer->name,
            'phone' => $customer->phone,
            'active' => true,
            'remove_photo' => true,
        ]);

        Storage::disk('public')->assertMissing('shared/photo.jpg');
    }

    public function test_database_failure_preserves_old_photo_and_removes_new_orphan(): void
    {
        config(['filesystems.product_photo_disk' => 'r2_products']);
        Storage::fake('r2_products');
        Storage::disk('r2_products')->put('products/current.jpg', 'current-photo');
        $product = Product::factory()->create([
            'sku' => 'CURRENT-SKU',
            'photo_path' => 'products/current.jpg',
            'photo_disk' => 'r2_products',
        ]);
        Product::factory()->create(['sku' => 'DUPLICATE-SKU']);

        $updateFailed = false;

        try {
            app(ProductService::class)->update($product->id, [
                'sku' => 'DUPLICATE-SKU',
                'name' => $product->name,
                'sale_price' => $product->sale_price,
                'active' => true,
                'photo' => $this->fakeImage('replacement.jpg'),
            ]);

            $this->fail('A atualização deveria falhar pela duplicidade do SKU.');
        } catch (QueryException) {
            $updateFailed = true;
        }

        $product->refresh();

        $this->assertTrue($updateFailed);
        $this->assertSame('products/current.jpg', $product->photo_path);
        $this->assertSame('r2_products', $product->photo_disk);
        $this->assertSame(['products/current.jpg'], Storage::disk('r2_products')->allFiles('products'));
    }

    public function test_erp_routes_can_be_generated_as_relative_urls_and_cors_is_not_open(): void
    {
        $this->assertSame('/customers', route('customers.store', absolute: false));
        $this->assertSame('/sellers', route('sellers.store', absolute: false));
        $this->assertSame('/products', route('products.store', absolute: false));
        $this->assertSame('/sales', route('sales.store', absolute: false));

        $this->assertNotContains('*', config('cors.allowed_origins', []));
    }

    public function test_named_routes_use_https_domain_forwarded_by_a_trusted_proxy(): void
    {
        config(['trustedproxy.proxies' => ['10.0.0.1']]);

        Route::get('/test/route-url', fn (): string => route('dashboard'));

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
            ->withHeaders([
                'Host' => 'erp.example.test',
                'X-Forwarded-Host' => 'erp.example.test',
                'X-Forwarded-Proto' => 'https',
            ])
            ->get('/test/route-url')
            ->assertOk()
            ->assertSeeText('https://erp.example.test/dashboard');
    }
}
