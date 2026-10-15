<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PersonTypeIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_persons_table_replaces_customer_and_seller_tables(): void
    {
        $this->assertTrue(Schema::hasTable('persons'));
        $this->assertFalse(Schema::hasTable('customers'));
        $this->assertFalse(Schema::hasTable('sellers'));
        $this->assertTrue(Schema::hasColumns('persons', [
            'id',
            'name',
            'document',
            'phone',
            'email',
            'photo_path',
            'type',
            'active',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_customer_and_seller_routes_force_the_correct_person_type(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('customers.store'), [
            ...$this->validPayload('Cliente criado'),
            'type' => Person::TYPE_SELLER,
        ])->assertSessionHasNoErrors();

        $this->actingAs($user)->post(route('sellers.store'), [
            ...$this->validPayload('Vendedor criado'),
            'type' => Person::TYPE_CUSTOMER,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('persons', [
            'name' => 'Cliente criado',
            'type' => Person::TYPE_CUSTOMER,
        ]);
        $this->assertDatabaseHas('persons', [
            'name' => 'Vendedor criado',
            'type' => Person::TYPE_SELLER,
        ]);
    }

    public function test_lists_and_searches_never_mix_person_types(): void
    {
        $customer = Person::factory()->customer()->create(['name' => 'Pessoa exclusiva cliente']);
        $seller = Person::factory()->seller()->create(['name' => 'Pessoa exclusiva vendedor']);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('customers.index', ['show_inactive' => '1']))
            ->assertOk()
            ->assertSee($customer->name)
            ->assertDontSee($seller->name);
        $this->actingAs($user)->get(route('sellers.index', ['show_inactive' => '1']))
            ->assertOk()
            ->assertSee($seller->name)
            ->assertDontSee($customer->name);

        $this->actingAs($user)->get(route('customers.index', ['search' => 'Pessoa exclusiva']))
            ->assertSee($customer->name)
            ->assertDontSee($seller->name);
        $this->actingAs($user)->get(route('sellers.index', ['search' => 'Pessoa exclusiva']))
            ->assertSee($seller->name)
            ->assertDontSee($customer->name);
    }

    public function test_updates_preserve_type_and_reject_a_person_from_the_other_context(): void
    {
        $customer = Person::factory()->customer()->create();
        $seller = Person::factory()->seller()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('customers.update', $customer), [
            ...$this->validPayload('Cliente atualizado'),
            'type' => Person::TYPE_SELLER,
        ])->assertSessionHasNoErrors();
        $this->actingAs($user)->put(route('sellers.update', $seller), [
            ...$this->validPayload('Vendedor atualizado'),
            'type' => Person::TYPE_CUSTOMER,
        ])->assertSessionHasNoErrors();

        $this->assertSame(Person::TYPE_CUSTOMER, $customer->fresh()->type);
        $this->assertSame(Person::TYPE_SELLER, $seller->fresh()->type);

        $this->actingAs($user)
            ->put(route('customers.update', $seller), $this->validPayload('Tentativa inválida'))
            ->assertNotFound();
        $this->actingAs($user)
            ->put(route('sellers.update', $customer), $this->validPayload('Tentativa inválida'))
            ->assertNotFound();

        $this->assertSame('Cliente atualizado', $customer->fresh()->name);
        $this->assertSame('Vendedor atualizado', $seller->fresh()->name);
    }

    public function test_customer_and_seller_lists_render_active_and_inactive_badges(): void
    {
        Person::factory()->customer()->create(['active' => true]);
        Person::factory()->customer()->create(['active' => false]);
        Person::factory()->seller()->create(['active' => true]);
        Person::factory()->seller()->create(['active' => false]);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('customers.index', ['show_inactive' => '1']))
            ->assertOk()
            ->assertSee('Ativo')
            ->assertSee('Inativo')
            ->assertSee('bg-emerald-50', false)
            ->assertSee('bg-rose-50', false);
        $this->actingAs($user)->get(route('sellers.index', ['show_inactive' => '1']))
            ->assertOk()
            ->assertSee('Ativo')
            ->assertSee('Inativo')
            ->assertSee('bg-emerald-50', false)
            ->assertSee('bg-rose-50', false);
    }

    /**
     * @return array{name: string, document: string, phone: string, email: string, active: string}
     */
    private function validPayload(string $name): array
    {
        return [
            'name' => $name,
            'document' => '529.982.247-25',
            'phone' => '(44) 99999-0000',
            'email' => 'pessoa@example.com',
            'active' => '1',
        ];
    }
}
