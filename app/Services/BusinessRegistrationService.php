<?php

namespace App\Services;

use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class BusinessRegistrationService
{
    public function __construct(
        private DefaultPersonService $defaultPeople,
        private CatalogCredentialService $catalogCredentials,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $business = Business::query()->create([
                'trade_name' => $data['business_trade_name'],
                'legal_name' => $data['business_legal_name'],
                'document' => $data['business_document'],
                'phone' => $data['business_phone'],
                'email' => $data['business_email'],
            ]);

            return app(BusinessContext::class)->run($business, function () use ($business, $data): User {
                $user = $business->users()->create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Hash::make($data['password']),
                ]);

                $administrator = Role::findOrCreate('administrador', 'web');
                $user->assignRole($administrator);
                $this->defaultPeople->ensureDefaults();
                $this->catalogCredentials->rotate($user);

                return $user;
            });
        });
    }
}
