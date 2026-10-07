<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        if (! config('erp.admin.enabled')) {
            return;
        }

        $email = (string) config('erp.admin.email');
        $password = (string) config('erp.admin.password');

        if ($email === '' || $password === '') {
            $this->command?->warn('Configure ADMIN_EMAIL e ADMIN_PASSWORD para criar o administrador local.');

            return;
        }

        $administrator = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => (string) config('erp.admin.name'),
                'password' => $password,
                'email_verified_at' => now(),
            ],
        );

        $administrator->syncRoles([
            Role::findByName('administrador', 'web'),
        ]);
    }
}
