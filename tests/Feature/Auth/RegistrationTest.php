<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
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

    public function test_new_users_can_register_and_are_authenticated_without_global_permissions(): void
    {
        Event::fake([Registered::class]);

        $response = $this->post('/register', [
            'name' => '  Maria da Silva  ',
            'email' => 'MARIA@EXAMPLE.COM',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ]);

        $user = User::query()->where('email', 'maria@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('Maria da Silva', $user->name);
        $this->assertTrue(Hash::check('secure-password', $user->password));
        $this->assertFalse($user->roles()->exists());
        $this->assertFalse($user->permissions()->exists());
        Event::assertDispatched(Registered::class, fn (Registered $event): bool => $event->user->is($user));
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_registered_user_can_log_out_and_log_in(): void
    {
        $credentials = [
            'name' => 'João Pereira',
            'email' => 'joao@example.com',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ];

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

        $response = $this->post('/register', [
            'name' => 'Another User',
            'email' => 'EXISTING@EXAMPLE.COM',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ]);

        $this->assertGuest();
        $this->assertDatabaseCount('users', 1);
        $response
            ->assertSessionHasErrors('email')
            ->assertSessionHasErrors(['email' => 'Este e-mail já está cadastrado.']);
    }

    public function test_registration_rejects_a_short_password(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('password');
    }

    public function test_registration_rejects_a_password_without_matching_confirmation(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'secure-password',
            'password_confirmation' => 'different-password',
        ]);

        $this->assertGuest();
        $response
            ->assertSessionHasErrors('password')
            ->assertSessionHasErrors(['password' => 'A confirmação da senha não corresponde.']);
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
}
