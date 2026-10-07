<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CustomerControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitor_is_redirected_from_customer_list_to_login(): void
    {
        $response = $this->get(route('customers.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_visitor_cannot_create_customer(): void
    {
        $response = $this->post(route('customers.store'), $this->validPayload());

        $response->assertRedirect(route('login'));
        $this->assertDatabaseEmpty('customers');
    }

    public function test_visitor_cannot_update_customer(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->put(route('customers.update', $customer), $this->validPayload());

        $response->assertRedirect(route('login'));
        $this->assertSame($customer->name, $customer->fresh()->name);
    }

    public function test_authenticated_user_can_access_customer_list(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('customers.index'));

        $response
            ->assertOk()
            ->assertSee('Cadastre e gerencie seus clientes.')
            ->assertSee('Novo cliente')
            ->assertSee('Foto do cliente')
            ->assertSee('enctype="multipart/form-data"', false);
    }

    public function test_valid_name_and_phone_create_customer(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('customers.store'), [
            'name' => 'Maria Silva',
            'phone' => '(44) 99999-0000',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Cliente cadastrado com sucesso.')
            ->assertRedirect(route('customers.index'));
        $this->assertDatabaseHas('customers', [
            'name' => 'Maria Silva',
            'document' => null,
            'phone' => '44999990000',
            'email' => null,
            'photo_path' => null,
        ]);
    }

    public function test_authenticated_user_can_create_customer_with_valid_photo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $payload = $this->validPayload();
        $payload['photo'] = UploadedFile::fake()->image('cliente.jpg');

        $response = $this->actingAs($user)->post(route('customers.store'), $payload);

        $response->assertSessionHasNoErrors();
        $customer = Customer::query()->firstOrFail();
        $this->assertNotNull($customer->photo_path);
        $this->assertStringStartsWith('customers/', $customer->photo_path);
        $this->assertNotSame('customers/cliente.jpg', $customer->photo_path);
        Storage::disk('public')->assertExists($customer->photo_path);
    }

    public function test_invalid_customer_photo_format_is_rejected(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $payload = $this->validPayload();
        $payload['photo'] = UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf');

        $response = $this
            ->actingAs($user)
            ->from(route('customers.index'))
            ->post(route('customers.store'), $payload);

        $response
            ->assertSessionHasErrors(['photo' => 'A foto deve estar no formato JPG, JPEG, PNG ou WEBP.'])
            ->assertRedirect(route('customers.index'));
        $this->assertDatabaseEmpty('customers');
    }

    public function test_customer_photo_larger_than_two_megabytes_is_rejected(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $payload = $this->validPayload();
        $payload['photo'] = UploadedFile::fake()->image('cliente.jpg')->size(2049);

        $response = $this
            ->actingAs($user)
            ->from(route('customers.index'))
            ->post(route('customers.store'), $payload);

        $response
            ->assertSessionHasErrors(['photo' => 'A foto deve ter no máximo 2 MB.'])
            ->assertRedirect(route('customers.index'));
        $this->assertDatabaseEmpty('customers');
    }

    public function test_all_valid_fields_create_customer(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('customers.store'), [
            'id' => 999,
            'name' => 'Empresa Exemplo Ltda',
            'document' => '12.345.678/0001-90',
            'phone' => '(11) 3333-4444',
            'email' => 'contato@empresa.com.br',
        ]);

        $response->assertRedirect(route('customers.index'));
        $this->assertDatabaseHas('customers', [
            'name' => 'Empresa Exemplo Ltda',
            'document' => '12345678000190',
            'phone' => '1133334444',
            'email' => 'contato@empresa.com.br',
        ]);
        $this->assertDatabaseMissing('customers', ['id' => 999]);
    }

    public function test_name_is_required_to_create_customer(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('customers.index'))
            ->post(route('customers.store'), [
                'phone' => '(44) 99999-0000',
            ]);

        $response
            ->assertSessionHasErrors(['name' => 'O nome é obrigatório.'])
            ->assertRedirect(route('customers.index'));
        $this->assertDatabaseEmpty('customers');
    }

    public function test_phone_is_required_to_create_customer(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('customers.index'))
            ->post(route('customers.store'), [
                'name' => 'Maria Silva',
            ]);

        $response
            ->assertSessionHasErrors(['phone' => 'O telefone é obrigatório.'])
            ->assertRedirect(route('customers.index'));
        $this->assertDatabaseEmpty('customers');
    }

    public function test_document_is_optional(): void
    {
        $user = User::factory()->create();
        $payload = $this->validPayload();
        unset($payload['document']);

        $response = $this->actingAs($user)->post(route('customers.store'), $payload);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('customers', [
            'name' => $payload['name'],
            'document' => null,
        ]);
    }

    public function test_email_is_optional(): void
    {
        $user = User::factory()->create();
        $payload = $this->validPayload();
        unset($payload['email']);

        $response = $this->actingAs($user)->post(route('customers.store'), $payload);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('customers', [
            'name' => $payload['name'],
            'email' => null,
        ]);
    }

    public function test_invalid_email_is_rejected(): void
    {
        $user = User::factory()->create();
        $payload = $this->validPayload();
        $payload['email'] = 'email-invalido';

        $response = $this
            ->actingAs($user)
            ->from(route('customers.index'))
            ->post(route('customers.store'), $payload);

        $response
            ->assertSessionHasErrors(['email' => 'Informe um e-mail válido.'])
            ->assertRedirect(route('customers.index'));
        $this->assertDatabaseEmpty('customers');
    }

    public function test_phone_is_stored_as_string_with_leading_zeros(): void
    {
        $user = User::factory()->create();
        $payload = $this->validPayload();
        $payload['phone'] = '(04) 43333-4444';

        $response = $this->actingAs($user)->post(route('customers.store'), $payload);

        $response->assertSessionHasNoErrors();
        $customer = Customer::query()->firstOrFail();
        $this->assertSame('04433334444', $customer->phone);
    }

    public function test_document_is_stored_as_string_with_leading_zeros(): void
    {
        $user = User::factory()->create();
        $payload = $this->validPayload();
        $payload['document'] = '012.345.678-90';

        $response = $this->actingAs($user)->post(route('customers.store'), $payload);

        $response->assertSessionHasNoErrors();
        $customer = Customer::query()->firstOrFail();
        $this->assertSame('01234567890', $customer->document);
    }

    public function test_cpf_is_stored_without_formatting(): void
    {
        $user = User::factory()->create();
        $payload = $this->validPayload();

        $response = $this->actingAs($user)->post(route('customers.store'), $payload);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('customers', ['document' => '12345678900']);
    }

    public function test_cnpj_is_stored_without_formatting(): void
    {
        $user = User::factory()->create();
        $payload = $this->validPayload();
        $payload['document'] = '12.345.678/0001-90';

        $response = $this->actingAs($user)->post(route('customers.store'), $payload);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('customers', ['document' => '12345678000190']);
    }

    public function test_phone_is_stored_without_formatting(): void
    {
        $user = User::factory()->create();
        $payload = $this->validPayload();

        $response = $this->actingAs($user)->post(route('customers.store'), $payload);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('customers', ['phone' => '44999990000']);
    }

    public function test_authenticated_user_can_update_customer(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create([
            'name' => 'Nome antigo',
            'phone' => '11111111111',
        ]);

        $response = $this->actingAs($user)->put(route('customers.update', $customer), [
            'name' => 'Nome atualizado',
            'document' => '98.765.432/0001-10',
            'phone' => '(22) 22222-2222',
            'email' => 'atualizado@example.com',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Cliente atualizado com sucesso.')
            ->assertRedirect(route('customers.index'));
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Nome atualizado',
            'document' => '98765432000110',
            'phone' => '22222222222',
            'email' => 'atualizado@example.com',
        ]);
    }

    public function test_update_preserves_leading_zeros(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();

        $response = $this->actingAs($user)->put(route('customers.update', $customer), [
            'name' => 'Cliente atualizado',
            'document' => '012.345.678-90',
            'phone' => '(04) 43333-4444',
            'email' => 'atualizado@example.com',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'document' => '01234567890',
            'phone' => '04433334444',
        ]);
    }

    public function test_update_without_new_photo_keeps_current_photo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $photoPath = 'customers/current-photo.jpg';
        Storage::disk('public')->put($photoPath, 'photo-content');
        $customer = Customer::factory()->create(['photo_path' => $photoPath]);

        $response = $this->actingAs($user)->put(
            route('customers.update', $customer),
            $this->validPayload(),
        );

        $response->assertSessionHasNoErrors();
        $this->assertSame($photoPath, $customer->fresh()->photo_path);
        Storage::disk('public')->assertExists($photoPath);
    }

    public function test_replacing_customer_photo_updates_path_and_removes_old_file(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $oldPhotoPath = 'customers/old-photo.jpg';
        Storage::disk('public')->put($oldPhotoPath, 'old-photo-content');
        $customer = Customer::factory()->create(['photo_path' => $oldPhotoPath]);
        $payload = $this->validPayload();
        $payload['photo'] = UploadedFile::fake()->image('new-photo.png');

        $response = $this->actingAs($user)->put(route('customers.update', $customer), $payload);

        $response->assertSessionHasNoErrors();
        $newPhotoPath = $customer->fresh()->photo_path;
        $this->assertNotNull($newPhotoPath);
        $this->assertNotSame($oldPhotoPath, $newPhotoPath);
        Storage::disk('public')->assertExists($newPhotoPath);
        Storage::disk('public')->assertMissing($oldPhotoPath);
    }

    public function test_authenticated_user_can_remove_customer_photo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $photoPath = 'customers/photo-to-remove.webp';
        Storage::disk('public')->put($photoPath, 'photo-content');
        $customer = Customer::factory()->create(['photo_path' => $photoPath]);
        $payload = $this->validPayload();
        $payload['remove_photo'] = '1';

        $response = $this->actingAs($user)->put(route('customers.update', $customer), $payload);

        $response->assertSessionHasNoErrors();
        $this->assertNull($customer->fresh()->photo_path);
        Storage::disk('public')->assertMissing($photoPath);
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function searchCriteria(): array
    {
        return [
            'name' => ['Localizável', 'name', 'Cliente Localizável'],
            'document' => ['987.765.432-10', 'document', '98776543210'],
            'phone' => ['(44) 98888-7777', 'phone', '44988887777'],
            'email' => ['busca@example.com', 'email', 'busca@example.com'],
        ];
    }

    #[DataProvider('searchCriteria')]
    public function test_search_finds_customer_by_supported_field(string $search, string $field, string $value): void
    {
        $user = User::factory()->create();
        $targetData = [
            'name' => 'Cliente encontrado',
            'document' => '11122233344',
            'phone' => '11911112222',
            'email' => 'encontrado@example.com',
            $field => $value,
        ];
        Customer::factory()->create($targetData);
        Customer::factory()->create([
            'name' => 'Cliente ignorado',
            'document' => '55566677788',
            'phone' => '22933334444',
            'email' => 'ignorado@example.com',
        ]);

        $response = $this->actingAs($user)->get(route('customers.index', ['search' => $search]));

        $response
            ->assertOk()
            ->assertSee($targetData['name'])
            ->assertDontSee('Cliente ignorado');
    }

    public function test_search_finds_stored_cpf_when_term_has_mask(): void
    {
        $user = User::factory()->create();
        Customer::factory()->create([
            'name' => 'Cliente do CPF',
            'document' => '01234567890',
        ]);

        $response = $this->actingAs($user)->get(route('customers.index', [
            'search' => '012.345.678-90',
        ]));

        $response->assertOk()->assertSee('Cliente do CPF');
    }

    public function test_search_finds_stored_phone_when_term_has_mask(): void
    {
        $user = User::factory()->create();
        Customer::factory()->create([
            'name' => 'Cliente do telefone',
            'phone' => '44999999999',
        ]);

        $response = $this->actingAs($user)->get(route('customers.index', [
            'search' => '(44) 99999-9999',
        ]));

        $response->assertOk()->assertSee('Cliente do telefone');
    }

    public function test_customer_list_formats_document_and_phone_without_changing_storage(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create([
            'document' => '01234567890',
            'phone' => '44999999999',
        ]);

        $response = $this->actingAs($user)->get(route('customers.index'));

        $response
            ->assertOk()
            ->assertSee('012.345.678-90')
            ->assertSee('(44) 99999-9999');
        $this->assertSame('01234567890', $customer->fresh()->document);
        $this->assertSame('44999999999', $customer->fresh()->phone);
    }

    public function test_customer_list_is_paginated_with_exactly_five_records_and_preserves_search(): void
    {
        $user = User::factory()->create();
        Customer::factory()
            ->count(11)
            ->sequence(fn (Sequence $sequence): array => [
                'name' => sprintf('Cliente %02d', $sequence->index + 1),
            ])
            ->create();

        $response = $this->actingAs($user)->get(route('customers.index', ['search' => 'Cliente']));

        $response
            ->assertOk()
            ->assertSee('Cliente 01')
            ->assertSee('Cliente 05')
            ->assertDontSee('Cliente 06')
            ->assertSee('Exibindo 1 a 5 de 11 registros')
            ->assertDontSee('Showing 1 to 5 of 11 results')
            ->assertViewHas('customers', function (LengthAwarePaginator $customers): bool {
                return $customers->perPage() === 5
                    && $customers->count() === 5
                    && $customers->total() === 11
                    && str_contains((string) $customers->nextPageUrl(), 'search=Cliente');
            });
    }

    public function test_second_customer_page_displays_the_correct_record_range(): void
    {
        $user = User::factory()->create();
        Customer::factory()
            ->count(11)
            ->sequence(fn (Sequence $sequence): array => [
                'name' => sprintf('Cliente %02d', $sequence->index + 1),
            ])
            ->create();

        $response = $this->actingAs($user)->get(route('customers.index', [
            'search' => 'Cliente',
            'page' => 2,
        ]));

        $response
            ->assertOk()
            ->assertSee('Cliente 06')
            ->assertSee('Cliente 10')
            ->assertDontSee('Cliente 05')
            ->assertDontSee('Cliente 11')
            ->assertSee('Exibindo 6 a 10 de 11 registros');
    }

    public function test_customer_values_are_escaped_in_list(): void
    {
        $user = User::factory()->create();
        $unsafeName = '<script>alert("xss")</script>';
        Customer::factory()->create(['name' => $unsafeName]);

        $response = $this->actingAs($user)->get(route('customers.index'));

        $response
            ->assertSee($unsafeName)
            ->assertDontSee($unsafeName, false);
    }

    public function test_visitor_cannot_delete_customer(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->delete(route('customers.destroy', $customer));

        $response->assertRedirect(route('login'));
        $this->assertModelExists($customer);
    }

    public function test_authenticated_user_can_delete_customer(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $photoPath = 'customers/photo-to-delete.jpg';
        Storage::disk('public')->put($photoPath, 'photo-content');
        $customer = Customer::factory()->create(['photo_path' => $photoPath]);

        $response = $this->actingAs($user)->delete(route('customers.destroy', $customer));

        $response
            ->assertSessionHas('success', 'Cliente excluído com sucesso.')
            ->assertRedirect(route('customers.index'));
        $this->assertModelMissing($customer);
        Storage::disk('public')->assertMissing($photoPath);
    }

    public function test_deleting_customer_does_not_remove_other_customers(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $otherCustomer = Customer::factory()->create();

        $response = $this->actingAs($user)->delete(route('customers.destroy', $customer));

        $response->assertRedirect(route('customers.index'));
        $this->assertModelMissing($customer);
        $this->assertModelExists($otherCustomer);
    }

    /**
     * @return array{name: string, document: string, phone: string, email: string}
     */
    private function validPayload(): array
    {
        return [
            'name' => 'Cliente Teste',
            'document' => '123.456.789-00',
            'phone' => '(44) 99999-0000',
            'email' => 'cliente@example.com',
        ];
    }
}
