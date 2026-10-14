<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Person;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SellerControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitor_cannot_deactivate_seller(): void
    {
        $seller = Person::factory()->seller()->create();

        $this->patch(route('sellers.deactivate', $seller))
            ->assertRedirect(route('login'));

        $this->assertTrue($seller->fresh()->active);
    }

    public function test_visitor_cannot_access_sellers(): void
    {
        $this->get(route('sellers.index'))->assertRedirect(route('login'));
    }

    public function test_visitor_cannot_change_sellers(): void
    {
        $seller = Person::factory()->seller()->create();

        $this->post(route('sellers.store'), $this->validPayload())->assertRedirect(route('login'));
        $this->put(route('sellers.update', $seller), $this->validPayload())->assertRedirect(route('login'));

        $this->assertDatabaseCount('persons', 1);
        $this->assertModelExists($seller);
    }

    public function test_authenticated_user_can_access_seller_list(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('sellers.index'));

        $response->assertOk()
            ->assertSee('Cadastre e gerencie seus vendedores.')
            ->assertSee('Novo vendedor')
            ->assertSee('Foto do vendedor')
            ->assertSee('CPF/CNPJ')
            ->assertSee('Nenhum vendedor encontrado')
            ->assertSee('colspan="6"', false)
            ->assertSee('@change="$el.form.requestSubmit()"', false)
            ->assertSee('enctype="multipart/form-data"', false);
    }

    public function test_authenticated_user_can_create_seller_without_photo(): void
    {
        $response = $this->actingAs(User::factory()->create())->post(route('sellers.store'), [
            'name' => 'Ana Costa',
            'phone' => '(04) 49999-0000',
            'email' => 'ana@example.com',
            'active' => '1',
        ]);

        $response->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Vendedor cadastrado com sucesso.')
            ->assertRedirect(route('sellers.index'));
        $this->assertDatabaseHas('persons', [
            'name' => 'Ana Costa',
            'document' => null,
            'phone' => '04499990000',
            'email' => 'ana@example.com',
            'photo_path' => null,
        ]);
    }

    public function test_seller_can_be_created_with_cpf_stored_without_formatting(): void
    {
        $payload = $this->validPayload();
        $payload['document'] = '012.345.678-90';

        $response = $this->actingAs(User::factory()->create())->post(route('sellers.store'), $payload);

        $response->assertSessionHasNoErrors();
        $seller = Person::query()->where('type', Person::TYPE_SELLER)->firstOrFail();
        $this->assertSame('01234567890', $seller->document);
    }

    public function test_seller_can_be_created_with_cnpj_stored_without_formatting(): void
    {
        $payload = $this->validPayload();
        $payload['document'] = '12.345.678/0001-90';

        $response = $this->actingAs(User::factory()->create())->post(route('sellers.store'), $payload);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('persons', ['document' => '12345678000190']);
    }

    #[DataProvider('invalidDocuments')]
    public function test_document_with_invalid_length_is_rejected(string $document): void
    {
        $payload = $this->validPayload();
        $payload['document'] = $document;

        $response = $this->actingAs(User::factory()->create())
            ->from(route('sellers.index'))
            ->post(route('sellers.store'), $payload);

        $response->assertSessionHasErrors(['document' => 'Informe um CPF com 11 dígitos ou CNPJ com 14 dígitos.']);
        $this->assertDatabaseEmpty('persons');
    }

    /** @return array<string, array{string}> */
    public static function invalidDocuments(): array
    {
        return ['ten digits' => ['1234567890'], 'twelve digits' => ['123456789012']];
    }

    public function test_name_is_required(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->from(route('sellers.index'))
            ->post(route('sellers.store'), ['phone' => '(44) 99999-0000']);

        $response->assertSessionHasErrors(['name' => 'O nome é obrigatório.']);
        $this->assertDatabaseEmpty('persons');
    }

    public function test_phone_is_required(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->from(route('sellers.index'))
            ->post(route('sellers.store'), ['name' => 'Ana Costa']);

        $response->assertSessionHasErrors(['phone' => 'O telefone é obrigatório.']);
        $this->assertDatabaseEmpty('persons');
    }

    #[DataProvider('invalidPhones')]
    public function test_invalid_phone_is_rejected(string $phone): void
    {
        $payload = $this->validPayload();
        $payload['phone'] = $phone;

        $response = $this->actingAs(User::factory()->create())
            ->from(route('sellers.index'))
            ->post(route('sellers.store'), $payload);

        $response->assertSessionHasErrors(['phone' => 'Informe um telefone com 10 ou 11 dígitos.']);
        $this->assertDatabaseEmpty('persons');
    }

    /** @return array<string, array{string}> */
    public static function invalidPhones(): array
    {
        return ['too short' => ['999999999'], 'too long' => ['119999999999']];
    }

    public function test_invalid_email_is_rejected(): void
    {
        $payload = $this->validPayload();
        $payload['email'] = 'email-invalido';

        $response = $this->actingAs(User::factory()->create())
            ->from(route('sellers.index'))
            ->post(route('sellers.store'), $payload);

        $response->assertSessionHasErrors(['email' => 'Informe um e-mail válido.']);
        $this->assertDatabaseEmpty('persons');
    }

    public function test_seller_can_be_created_with_valid_photo(): void
    {
        Storage::fake('public');
        $payload = $this->validPayload();
        $payload['photo'] = $this->fakeImage('vendedor.jpg')->size(5120);

        $response = $this->actingAs(User::factory()->create())->post(route('sellers.store'), $payload);

        $response->assertSessionHasNoErrors();
        $seller = Person::query()->where('type', Person::TYPE_SELLER)->firstOrFail();
        $this->assertNotNull($seller->photo_path);
        $this->assertStringStartsWith('persons/', $seller->photo_path);
        $this->assertNotSame('persons/vendedor.jpg', $seller->photo_path);
        Storage::disk('public')->assertExists($seller->photo_path);
    }

    public function test_invalid_photo_format_is_rejected(): void
    {
        Storage::fake('public');
        $payload = $this->validPayload();
        $payload['photo'] = UploadedFile::fake()->create('arquivo.pdf', 100, 'application/pdf');

        $response = $this->actingAs(User::factory()->create())
            ->from(route('sellers.index'))
            ->post(route('sellers.store'), $payload);

        $response->assertSessionHasErrors(['photo' => 'A foto deve estar no formato JPG, JPEG, PNG ou WEBP.']);
        $this->assertDatabaseEmpty('persons');
    }

    public function test_photo_larger_than_five_megabytes_is_rejected(): void
    {
        Storage::fake('public');
        $payload = $this->validPayload();
        $payload['photo'] = $this->fakeImage('vendedor.jpg')->size(5121);

        $response = $this->actingAs(User::factory()->create())
            ->from(route('sellers.index'))
            ->post(route('sellers.store'), $payload);

        $response->assertSessionHasErrors(['photo' => 'A foto deve ter no máximo 5 MB.']);
        $this->assertDatabaseEmpty('persons');
    }

    public function test_authenticated_user_can_update_seller(): void
    {
        $seller = Person::factory()->seller()->create(['name' => 'Nome antigo']);

        $response = $this->actingAs(User::factory()->create())->put(route('sellers.update', $seller), [
            'name' => 'Nome atualizado',
            'phone' => '(11) 3333-4444',
            'email' => 'atualizado@example.com',
            'active' => '1',
        ]);

        $response->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Vendedor atualizado com sucesso.')
            ->assertRedirect(route('sellers.index'));
        $this->assertDatabaseHas('persons', [
            'id' => $seller->id,
            'name' => 'Nome atualizado',
            'phone' => '1133334444',
            'email' => 'atualizado@example.com',
        ]);
    }

    public function test_authenticated_user_can_update_seller_document(): void
    {
        $seller = Person::factory()->seller()->create(['document' => '12345678901']);
        $payload = $this->validPayload();
        $payload['document'] = '98.765.432/0001-10';

        $response = $this->actingAs(User::factory()->create())->put(route('sellers.update', $seller), $payload);

        $response->assertSessionHasNoErrors();
        $this->assertSame('98765432000110', $seller->fresh()->document);
    }

    public function test_authenticated_user_can_remove_seller_document(): void
    {
        $seller = Person::factory()->seller()->create(['document' => '12345678901']);
        $payload = $this->validPayload();
        $payload['document'] = '';

        $response = $this->actingAs(User::factory()->create())->put(route('sellers.update', $seller), $payload);

        $response->assertSessionHasNoErrors();
        $this->assertNull($seller->fresh()->document);
    }

    public function test_update_without_new_photo_keeps_current_photo(): void
    {
        Storage::fake('public');
        $path = 'persons/current.jpg';
        Storage::disk('public')->put($path, 'content');
        $seller = Person::factory()->seller()->create(['photo_path' => $path]);

        $response = $this->actingAs(User::factory()->create())->put(route('sellers.update', $seller), $this->validPayload());

        $response->assertSessionHasNoErrors();
        $this->assertSame($path, $seller->fresh()->photo_path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_replacing_photo_updates_path_and_removes_old_file(): void
    {
        Storage::fake('public');
        $oldPath = 'persons/old.jpg';
        Storage::disk('public')->put($oldPath, 'old');
        $seller = Person::factory()->seller()->create(['photo_path' => $oldPath]);
        $payload = $this->validPayload();
        $payload['photo'] = $this->fakeImage('new.png');

        $response = $this->actingAs(User::factory()->create())->put(route('sellers.update', $seller), $payload);

        $response->assertSessionHasNoErrors();
        $newPath = $seller->fresh()->photo_path;
        $this->assertNotNull($newPath);
        $this->assertNotSame($oldPath, $newPath);
        Storage::disk('public')->assertExists($newPath);
        Storage::disk('public')->assertMissing($oldPath);
    }

    public function test_authenticated_user_can_remove_photo(): void
    {
        Storage::fake('public');
        $path = 'persons/remove.webp';
        Storage::disk('public')->put($path, 'content');
        $seller = Person::factory()->seller()->create(['photo_path' => $path]);
        $payload = $this->validPayload();
        $payload['remove_photo'] = '1';

        $response = $this->actingAs(User::factory()->create())->put(route('sellers.update', $seller), $payload);

        $response->assertSessionHasNoErrors();
        $this->assertNull($seller->fresh()->photo_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_new_seller_uses_active_database_default(): void
    {
        $seller = Person::query()->create([
            'type' => Person::TYPE_SELLER,
            'name' => 'Vendedor padrão',
            'phone' => '44999990000',
        ]);

        $this->assertTrue($seller->refresh()->active);
    }

    public function test_seller_creation_ignores_manipulated_inactive_value(): void
    {
        $activePayload = $this->validPayload();
        $inactivePayload = $this->validPayload();
        $inactivePayload['name'] = 'Vendedor inativo';
        $inactivePayload['active'] = '0';

        $this->actingAs(User::factory()->create())->post(route('sellers.store'), $activePayload)->assertSessionHasNoErrors();
        $this->actingAs(User::factory()->create())->post(route('sellers.store'), $inactivePayload)->assertSessionHasNoErrors();

        $this->assertTrue(Person::query()->where('type', Person::TYPE_SELLER)->where('name', 'Vendedor Teste')->firstOrFail()->active);
        $this->assertTrue(Person::query()->where('type', Person::TYPE_SELLER)->where('name', 'Vendedor inativo')->firstOrFail()->active);
    }

    public function test_seller_can_change_between_active_and_inactive_without_removing_record_or_photo(): void
    {
        Storage::fake('public');
        $photoPath = 'persons/status-photo.jpg';
        Storage::disk('public')->put($photoPath, 'content');
        $seller = Person::factory()->seller()->create(['active' => true, 'photo_path' => $photoPath]);
        $payload = $this->validPayload();
        $payload['active'] = '0';

        $this->actingAs(User::factory()->create())->put(route('sellers.update', $seller), $payload)->assertSessionHasNoErrors();

        $this->assertModelExists($seller);
        $this->assertFalse($seller->fresh()->active);
        $this->assertSame($photoPath, $seller->fresh()->photo_path);
        Storage::disk('public')->assertExists($photoPath);

        $payload['active'] = '1';
        $this->actingAs(User::factory()->create())->put(route('sellers.update', $seller), $payload)->assertSessionHasNoErrors();
        $this->assertTrue($seller->fresh()->active);
    }

    public function test_seller_delete_route_does_not_exist(): void
    {
        $this->assertFalse(Route::has('sellers.destroy'));
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function searchCriteria(): array
    {
        return [
            'name' => ['Localizável', 'name', 'Vendedor Localizável'],
            'cpf without mask' => ['01234567890', 'document', '01234567890'],
            'cpf with mask' => ['012.345.678-90', 'document', '01234567890'],
            'cnpj without mask' => ['12345678000190', 'document', '12345678000190'],
            'cnpj with mask' => ['12.345.678/0001-90', 'document', '12345678000190'],
            'phone without mask' => ['44988887777', 'phone', '44988887777'],
            'phone with mask' => ['(44) 98888-7777', 'phone', '44988887777'],
            'email' => ['busca@example.com', 'email', 'busca@example.com'],
        ];
    }

    #[DataProvider('searchCriteria')]
    public function test_search_finds_seller_by_supported_field(string $search, string $field, string $value): void
    {
        $target = Person::factory()->seller()->create([
            'name' => 'Vendedor encontrado',
            'document' => '11122233344',
            'phone' => '11911112222',
            'email' => 'encontrado@example.com',
            $field => $value,
        ]);
        Person::factory()->seller()->create([
            'name' => 'Vendedor ignorado',
            'document' => '55566677788',
            'phone' => '22933334444',
            'email' => 'ignorado@example.com',
        ]);

        $response = $this->actingAs(User::factory()->create())->get(route('sellers.index', ['search' => $search]));

        $response->assertOk()->assertSee($target->name)->assertDontSee('Vendedor ignorado');
    }

    public function test_list_formats_document_and_phone_without_changing_storage(): void
    {
        $seller = Person::factory()->seller()->create([
            'document' => '01234567890',
            'phone' => '44999999999',
        ]);

        $response = $this->actingAs(User::factory()->create())->get(route('sellers.index'));

        $response->assertOk()
            ->assertSee('012.345.678-90')
            ->assertSee('(44) 99999-9999');
        $this->assertSame('01234567890', $seller->fresh()->document);
        $this->assertSame('44999999999', $seller->fresh()->phone);
    }

    public function test_seller_list_hides_inactive_by_default_and_can_show_all_statuses(): void
    {
        $active = Person::factory()->seller()->create(['name' => 'Vendedor ativo', 'active' => true]);
        $inactive = Person::factory()->seller()->create(['name' => 'Vendedor inativo oculto', 'active' => false]);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('sellers.index'))
            ->assertOk()
            ->assertSee($active->name)
            ->assertDontSee($inactive->name)
            ->assertViewHas('showInactive', false);

        $this->actingAs($user)->get(route('sellers.index', ['show_inactive' => '1']))
            ->assertOk()
            ->assertSee($active->name)
            ->assertSee($inactive->name)
            ->assertViewHas('showInactive', true)
            ->assertSee('value="1" checked', false);
    }

    public function test_seller_search_works_with_inactive_filter_without_mixing_customers(): void
    {
        $seller = Person::factory()->seller()->create(['name' => 'Pessoa filtrada vendedor', 'active' => false]);
        $customer = Person::factory()->customer()->create(['name' => 'Pessoa filtrada cliente', 'active' => false]);

        $response = $this->actingAs(User::factory()->create())->get(route('sellers.index', [
            'search' => 'Pessoa filtrada',
            'show_inactive' => '1',
        ]));

        $response->assertOk()->assertSee($seller->name)->assertDontSee($customer->name);
    }

    public function test_seller_list_is_paginated_with_five_records_and_preserves_search(): void
    {
        Person::factory()->seller()->count(11)->sequence(fn (Sequence $sequence): array => [
            'name' => sprintf('Vendedor %02d', $sequence->index + 1),
        ])->create();

        $response = $this->actingAs(User::factory()->create())->get(route('sellers.index', [
            'search' => 'Vendedor',
            'show_inactive' => '1',
        ]));

        $response->assertOk()
            ->assertSee('Vendedor 01')
            ->assertSee('Vendedor 05')
            ->assertDontSee('Vendedor 06')
            ->assertSee('Exibindo 1 a 5 de 11 registros')
            ->assertViewHas('sellers', function (LengthAwarePaginator $sellers): bool {
                return $sellers->perPage() === 5
                    && $sellers->count() === 5
                    && $sellers->total() === 11
                    && str_contains((string) $sellers->nextPageUrl(), 'search=Vendedor')
                    && str_contains((string) $sellers->nextPageUrl(), 'show_inactive=1');
            });
    }

    public function test_authenticated_user_can_deactivate_seller_without_deleting_it(): void
    {
        $seller = Person::factory()->seller()->create(['name' => 'Vendedor para inativar']);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('sellers.index'))
            ->assertSee(route('sellers.deactivate', $seller), false)
            ->assertSee('Excluir vendedor?');

        $this->actingAs($user)
            ->patch(route('sellers.deactivate', $seller))
            ->assertRedirect(route('sellers.index'))
            ->assertSessionHas('success', 'Vendedor inativado com sucesso.');

        $this->assertModelExists($seller);
        $this->assertFalse($seller->fresh()->active);

        $this->actingAs($user)->get(route('sellers.index'))
            ->assertDontSee($seller->name);
        $this->actingAs($user)->get(route('sellers.index', ['show_inactive' => '1']))
            ->assertSee($seller->name)
            ->assertDontSee(route('sellers.deactivate', $seller), false);
    }

    public function test_seller_has_deactivation_but_no_physical_delete_route(): void
    {
        $this->assertTrue(Route::has('sellers.deactivate'));
        $this->assertFalse(Route::has('sellers.destroy'));

        $seller = Person::factory()->seller()->create();
        $this->actingAs(User::factory()->create())
            ->delete("/sellers/{$seller->id}")
            ->assertMethodNotAllowed();
    }

    /**
     * @return array{name: string, document: string, phone: string, email: string, active: string}
     */
    private function validPayload(): array
    {
        return [
            'name' => 'Vendedor Teste',
            'document' => '123.456.789-00',
            'phone' => '(44) 99999-0000',
            'email' => 'vendedor@example.com',
            'active' => '1',
        ];
    }
}
