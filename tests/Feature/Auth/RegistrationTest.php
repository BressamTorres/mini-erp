<?php

namespace Tests\Feature\Auth;

use App\Models\Business;
use App\Models\Person;
use App\Models\Product;
use App\Models\User;
use App\Services\BusinessContext;
use App\Services\DefaultPersonService;
use App\Services\SaleService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response
            ->assertOk()
            ->assertSee('Criar conta')
            ->assertSee('Nome completo')
            ->assertSee('Confirmar senha');
    }

    public function test_new_users_can_register_a_business_and_are_authenticated_as_its_administrator(): void
    {
        Event::fake([Registered::class]);

        $response = $this->post('/register', $this->validRegistration([
            'name' => '  Maria da Silva  ',
            'email' => 'MARIA@EXAMPLE.COM',
        ]));

        $user = User::query()->where('email', 'maria@example.com')->firstOrFail();
        $business = Business::query()->findOrFail($user->business_id);

        $this->assertAuthenticatedAs($user);
        $this->assertSame('Maria da Silva', $user->name);
        $this->assertSame('Empresa Teste', $business->trade_name);
        $this->assertSame('11222333000181', $business->document);
        $this->assertTrue(Hash::check('secure-password', $user->password));
        app(BusinessContext::class)->run($business, function () use ($user): void {
            $this->assertTrue($user->hasRole('administrador'));
            $this->assertSame($user->business_id, $user->roles()->sole()->business_id);
        });
        Event::assertDispatched(Registered::class, fn (Registered $event): bool => $event->user->is($user));
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_registration_creates_isolated_idempotent_default_people_available_to_sales(): void
    {
        $this->post('/register', $this->validRegistration([
            'email' => 'first@example.com',
            'business_trade_name' => 'Empresa Alfa',
            'business_legal_name' => 'Empresa Alfa Ltda.',
            'business_document' => '52998224725',
            'business_email' => 'alfa@example.com',
        ]))->assertRedirect(route('dashboard', absolute: false));
        $firstUser = User::query()->where('email', 'first@example.com')->sole();

        $this->post('/logout')->assertRedirect(route('login'));
        $this->post('/register', $this->validRegistration([
            'email' => 'second@example.com',
            'business_trade_name' => 'Empresa Beta',
            'business_legal_name' => 'Empresa Beta Ltda.',
            'business_document' => '11222333000181',
            'business_email' => 'beta@example.com',
        ]))->assertRedirect(route('dashboard', absolute: false));
        $secondUser = User::query()->where('email', 'second@example.com')->sole();

        $this->assertNotSame($firstUser->business_id, $secondUser->business_id);

        foreach ([$firstUser, $secondUser] as $user) {
            app(BusinessContext::class)->run($user->business_id, function () use ($user): void {
                $customer = Person::query()->where('system_key', Person::SYSTEM_DEFAULT_CUSTOMER)->sole();
                $seller = Person::query()->where('system_key', Person::SYSTEM_DEFAULT_SELLER)->sole();

                $this->assertSame($user->business_id, $customer->business_id);
                $this->assertSame('Consumidor Final', $customer->name);
                $this->assertSame(Person::TYPE_CUSTOMER, $customer->type);
                $this->assertTrue($customer->active);
                $this->assertSame($user->business_id, $seller->business_id);
                $this->assertSame('Vendedor Padrão', $seller->name);
                $this->assertSame(Person::TYPE_SELLER, $seller->type);
                $this->assertTrue($seller->active);
                $this->assertSame(2, Person::query()->count());

                app(DefaultPersonService::class)->ensureDefaults();
                app(DefaultPersonService::class)->ensureDefaults();

                $this->assertSame(2, Person::query()->count());
                $options = app(SaleService::class)->formOptions();
                $this->assertSame([$customer->id], collect($options['customers'])->pluck('id')->all());
                $this->assertSame([$seller->id], collect($options['sellers'])->pluck('id')->all());

                $product = Product::factory()->create(['sale_price' => '10.00']);
                $sale = app(SaleService::class)->create([
                    'customer_id' => $customer->id,
                    'seller_id' => $seller->id,
                    'items' => [['product_id' => $product->id, 'quantity' => 1]],
                ]);

                $this->assertSame($user->business_id, $sale->business_id);
                $this->assertSame($customer->id, $sale->customer_id);
                $this->assertSame($seller->id, $sale->seller_id);
            });
        }
    }

    public function test_registered_user_can_log_out_and_log_in(): void
    {
        $credentials = $this->validRegistration([
            'name' => 'João Pereira',
            'email' => 'joao@example.com',
            'business_document' => '52998224725',
        ]);

        $this->post('/register', $credentials);
        $this->post('/logout');

        $response = $this->post('/login', [
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_registration_rejects_a_duplicate_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->post('/register', $this->validRegistration([
            'name' => 'Another User',
            'email' => 'EXISTING@EXAMPLE.COM',
        ]));

        $this->assertGuest();
        $this->assertDatabaseCount('users', 1);
        $response
            ->assertSessionHasErrors('email')
            ->assertSessionHasErrors(['email' => 'Este e-mail já está cadastrado.']);
    }

    public function test_registration_rejects_a_short_password(): void
    {
        $response = $this->post('/register', $this->validRegistration([
            'password' => 'short',
            'password_confirmation' => 'short',
        ]));

        $this->assertGuest();
        $response->assertSessionHasErrors('password');
    }

    public function test_registration_rejects_a_password_without_matching_confirmation(): void
    {
        $response = $this->post('/register', $this->validRegistration([
            'password_confirmation' => 'different-password',
        ]));

        $this->assertGuest();
        $response
            ->assertSessionHasErrors('password')
            ->assertSessionHasErrors(['password' => 'A confirmação da senha não corresponde.']);
    }

    #[DataProvider('businessPhoneProvider')]
    public function test_registration_normalizes_fixed_and_mobile_phones(string $phone, string $normalizedPhone): void
    {
        $response = $this->post('/register', $this->validRegistration([
            'business_phone' => $phone,
        ]));

        $response->assertRedirect(route('dashboard', absolute: false));
        $business = User::query()->where('email', 'test@example.com')->sole()->business;
        $this->assertSame($normalizedPhone, $business->phone);
    }

    /** @return array<string, array{string, string}> */
    public static function businessPhoneProvider(): array
    {
        return [
            'masked fixed phone' => ['(44) 3333-4444', '4433334444'],
            'unmasked fixed phone' => ['4433334444', '4433334444'],
            'masked mobile phone' => ['(44) 99999-9999', '44999999999'],
            'unmasked mobile phone' => ['44999999999', '44999999999'],
        ];
    }

    #[DataProvider('businessDocumentProvider')]
    public function test_registration_normalizes_valid_cpf_and_cnpj_documents(string $document, string $normalizedDocument): void
    {
        $response = $this->post('/register', $this->validRegistration([
            'business_document' => $document,
        ]));

        $response->assertRedirect(route('dashboard', absolute: false));
        $business = User::query()->where('email', 'test@example.com')->sole()->business;
        $this->assertSame($normalizedDocument, $business->document);
    }

    /** @return array<string, array{string, string}> */
    public static function businessDocumentProvider(): array
    {
        return [
            'masked CPF' => ['529.982.247-25', '52998224725'],
            'unmasked CPF' => ['52998224725', '52998224725'],
            'masked numeric CNPJ' => ['11.222.333/0001-81', '11222333000181'],
            'unmasked numeric CNPJ' => ['11222333000181', '11222333000181'],
            'masked alphanumeric CNPJ' => ['12.ABC.345/01DE-35', '12ABC34501DE35'],
            'unmasked alphanumeric CNPJ' => ['12ABC34501DE35', '12ABC34501DE35'],
        ];
    }

    public function test_registration_rejects_phone_with_more_than_eleven_digits(): void
    {
        $response = $this->post('/register', $this->validRegistration([
            'business_phone' => '(44) 99999-99999',
        ]));

        $this->assertGuest();
        $response->assertSessionHasErrors([
            'business_phone' => 'Informe um telefone com 10 ou 11 dígitos.',
        ]);
    }

    public function test_registration_is_rate_limited(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.25']);

        $invalidRegistration = [
            'name' => '',
            'email' => 'limited@example.com',
            'password' => 'short',
            'password_confirmation' => 'different',
        ];

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post('/register', $invalidRegistration)
                ->assertSessionHasErrors();
        }

        $this->post('/register', $invalidRegistration)
            ->assertTooManyRequests();
    }

    /** @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function validRegistration(array $overrides = []): array
    {
        return [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
            'business_trade_name' => 'Empresa Teste',
            'business_legal_name' => 'Empresa Teste Ltda.',
            'business_document' => '11.222.333/0001-81',
            'business_phone' => '(11) 99999-9999',
            'business_email' => 'empresa@example.com',
            ...$overrides,
        ];
    }
}
