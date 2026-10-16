<?php

namespace Tests\Feature\Database;

use App\Models\Person;
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
            function () use ($administrator): void {
                $this->assertTrue($administrator->hasRole('administrador'));
                $this->assertDatabaseCount('persons', 2);
                $this->assertDatabaseHas('persons', [
                    'business_id' => $administrator->business_id,
                    'system_key' => Person::SYSTEM_DEFAULT_CUSTOMER,
                    'type' => Person::TYPE_CUSTOMER,
                    'name' => 'Consumidor Final',
                    'active' => true,
                ]);
                $this->assertDatabaseHas('persons', [
                    'business_id' => $administrator->business_id,
                    'system_key' => Person::SYSTEM_DEFAULT_SELLER,
                    'type' => Person::TYPE_SELLER,
                    'name' => 'Vendedor Padrão',
                    'active' => true,
                ]);
            },
        );
    }
}
