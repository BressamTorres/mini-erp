<?php

namespace Tests\Feature\Database;

use App\Models\User;
use App\Services\BusinessContext;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_idempotently_creates_administrator_with_role(): void
    {
        config()->set('erp.admin.enabled', true);
        config()->set('erp.admin.name', 'Administrador');
        config()->set('erp.admin.email', 'admin@example.com');
        config()->set('erp.admin.password', 'development-password');

        $this->seed(AdminUserSeeder::class);
        $this->seed(AdminUserSeeder::class);

        $administrator = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->assertSame(1, User::query()->count());
        $this->assertSame('Administrador', $administrator->name);
        $this->assertSame(
            config('erp.admin.business.document'),
            $administrator->business->document,
        );
        $this->assertTrue(Hash::check('development-password', $administrator->password));
        app(BusinessContext::class)->run(
            $administrator->business_id,
            fn () => $this->assertTrue($administrator->hasRole('administrador')),
        );
    }
}
