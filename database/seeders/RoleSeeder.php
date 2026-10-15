<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Services\BusinessContext;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Business::query()->eachById(function (Business $business): void {
            app(BusinessContext::class)->run(
                $business,
                fn (): Role => Role::findOrCreate('administrador', 'web'),
            );
        });
    }
}
