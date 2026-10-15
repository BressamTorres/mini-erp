<?php

namespace Tests\Feature;

use App\Models\Person;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\FormSubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class DuplicateSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_submission_service_executes_same_token_only_once(): void
    {
        $executions = 0;
        $token = (string) Str::uuid();
        $service = app(FormSubmissionService::class);

        $first = $service->run('test.store', 1, $token, [], function () use (&$executions): void {
            $executions++;
        });
        $second = $service->run('test.store', 1, $token, [], function () use (&$executions): void {
            $executions++;
        });

        $this->assertTrue($first);
        $this->assertFalse($second);
        $this->assertSame(1, $executions);
    }

    public function test_duplicate_customer_submission_creates_one_record_and_one_photo(): void
    {
        config(['filesystems.person_photo_disk' => 'r2_private']);
        Storage::fake('r2_private');
        $user = User::factory()->create();
        $token = (string) Str::uuid();
        $payload = [
            'name' => 'Cliente único',
            'phone' => '(44) 99999-9999',
            'submission_token' => $token,
        ];

        $this->actingAs($user)->post(route('customers.store'), [
            ...$payload,
            'photo' => $this->fakeImage('cliente.jpg'),
        ])->assertSessionHasNoErrors();
        $this->actingAs($user)->post(route('customers.store'), [
            ...$payload,
            'photo' => $this->fakeImage('cliente.jpg'),
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('persons', 1);
        $customer = Person::query()->firstOrFail();
        $this->assertSame('r2_private', $customer->photo_disk);
        $this->assertCount(1, Storage::disk('r2_private')->allFiles('businesses/'.$user->business_id.'/persons'));
    }

    public function test_duplicate_seller_submission_creates_one_record(): void
    {
        $user = User::factory()->create();
        $payload = [
            'name' => 'Vendedor único',
            'phone' => '(44) 99999-9999',
            'submission_token' => (string) Str::uuid(),
        ];

        $this->actingAs($user)->post(route('sellers.store'), $payload)->assertSessionHasNoErrors();
        $this->actingAs($user)->post(route('sellers.store'), $payload)->assertSessionHasNoErrors();

        $this->assertDatabaseCount('persons', 1);
        $this->assertSame(Person::TYPE_SELLER, Person::query()->firstOrFail()->type);
    }

    public function test_duplicate_product_update_does_not_upload_photo_twice(): void
    {
        config(['filesystems.product_photo_disk' => 'r2_products']);
        Storage::fake('r2_products');
        Storage::disk('r2_products')->put('products/old.jpg', 'old-photo');
        $product = Product::factory()->create([
            'photo_path' => 'products/old.jpg',
            'photo_disk' => 'r2_products',
        ]);
        $user = User::factory()->create();
        $token = (string) Str::uuid();
        $payload = [
            'sku' => $product->sku,
            'name' => 'Produto atualizado',
            'sale_price' => '199,90',
            'active' => '1',
            'submission_token' => $token,
        ];

        $this->actingAs($user)->put(route('products.update', $product), [
            ...$payload,
            'photo' => $this->fakeImage('nova.jpg'),
        ])->assertSessionHasNoErrors();
        $firstPhotoPath = $product->fresh()->photo_path;

        $this->actingAs($user)->put(route('products.update', $product), [
            ...$payload,
            'photo' => $this->fakeImage('duplicada.jpg'),
        ])->assertSessionHasNoErrors();

        $this->assertSame($firstPhotoPath, $product->fresh()->photo_path);
        $this->assertSame([$firstPhotoPath], Storage::disk('r2_products')->allFiles('businesses/'.$user->business_id.'/products'));
    }

    public function test_duplicate_sale_submission_creates_one_sale(): void
    {
        $customer = Person::factory()->customer()->create();
        $seller = Person::factory()->seller()->create();
        $product = Product::factory()->create();
        $user = User::factory()->create();
        $payload = [
            'customer_id' => $customer->id,
            'seller_id' => $seller->id,
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 2,
            ]],
            'submission_token' => (string) Str::uuid(),
        ];

        $this->actingAs($user)->post(route('sales.store'), $payload)->assertSessionHasNoErrors();
        $this->actingAs($user)->post(route('sales.store'), $payload)->assertSessionHasNoErrors();

        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseCount('sale_items', 1);
        $this->assertSame(1, Sale::query()->count());
    }

    public function test_validation_error_does_not_consume_submission_token(): void
    {
        $user = User::factory()->create();
        $token = (string) Str::uuid();

        $this->actingAs($user)->post(route('customers.store'), [
            'phone' => '(44) 99999-9999',
            'submission_token' => $token,
        ])->assertSessionHasErrors('name');

        $this->actingAs($user)->post(route('customers.store'), [
            'name' => 'Cliente após validação',
            'phone' => '(44) 99999-9999',
            'submission_token' => $token,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('persons', 1);
    }

    public function test_requests_without_token_receive_short_fingerprint_protection(): void
    {
        $user = User::factory()->create();
        $payload = [
            'name' => 'Cliente sem token',
            'phone' => '(44) 99999-9999',
        ];

        $this->actingAs($user)->post(route('customers.store'), $payload)->assertSessionHasNoErrors();
        $this->actingAs($user)->post(route('customers.store'), $payload)->assertSessionHasNoErrors();

        $this->assertDatabaseCount('persons', 1);
    }

    public function test_business_forms_render_submission_guard_and_loading_state(): void
    {
        $user = User::factory()->create();

        foreach (['customers.index', 'sellers.index', 'products.index', 'sales.index'] as $routeName) {
            $this->actingAs($user)
                ->get(route($routeName))
                ->assertOk()
                ->assertSee('name="submission_token"', false)
                ->assertSee('Salvando...');
        }

    }
}
