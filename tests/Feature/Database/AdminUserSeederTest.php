<?php

namespace Tests\Feature\Database;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_idempotently_creates_administrator_with_role(): void
    {
        config()->set('erp.admin', [
            'enabled' => true,
            'name' => 'Administrador',
            'email' => 'admin@example.com',
            'password' => 'development-password',
        ]);

        $this->seed(AdminUserSeeder::class);
        $this->seed(AdminUserSeeder::class);

        $administrator = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->assertSame(1, User::query()->count());
        $this->assertSame('Administrador', $administrator->name);
        $this->assertTrue(Hash::check('development-password', $administrator->password));
        $this->assertTrue($administrator->hasRole('administrador'));
    }
}
