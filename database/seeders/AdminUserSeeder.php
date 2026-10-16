<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Services\BusinessContext;
use App\Services\DefaultPersonService;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    public function __construct(private DefaultPersonService $defaultPeople) {}

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! config('erp.admin.enabled')) {
            return;
        }

        $email = (string) config('erp.admin.email');
        $password = (string) config('erp.admin.password');

        if ($email === '' || $password === '') {
            $this->command?->warn('Configure ADMIN_EMAIL e ADMIN_PASSWORD para criar o administrador local.');

            return;
        }

        $business = Business::query()->updateOrCreate(
            ['document' => (string) config('erp.admin.business.document')],
            [
                'trade_name' => (string) config('erp.admin.business.trade_name'),
                'legal_name' => (string) config('erp.admin.business.legal_name'),
                'phone' => (string) config('erp.admin.business.phone'),
                'email' => (string) config('erp.admin.business.email'),
            ],
        );

        app(BusinessContext::class)->run($business, function () use ($business, $email, $password): void {
            $administrator = $business->users()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => (string) config('erp.admin.name'),
                    'password' => $password,
                    'email_verified_at' => now(),
                ],
            );

            $administrator->syncRoles([
                Role::findOrCreate('administrador', 'web'),
            ]);

            $this->defaultPeople->ensureDefaults();
        });
    }
}
