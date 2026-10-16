<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use App\Services\BusinessContext;
use App\Services\BusinessRegistrationService;
use App\Services\CatalogCredentialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class BusinessCatalogProvisioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_unique_permanent_catalog_slugs(): void
    {
        $firstBusiness = Business::factory()->create(['trade_name' => 'Loja Fashion']);
        $secondBusiness = Business::factory()->create(['trade_name' => 'Loja Fashion']);

        $this->assertSame('loja-fashion', $firstBusiness->catalog_slug);
        $this->assertSame('loja-fashion-2', $secondBusiness->catalog_slug);

        $firstBusiness->update(['trade_name' => 'Nome Alterado']);

        $this->assertSame('loja-fashion', $firstBusiness->fresh()->catalog_slug);
    }

    public function test_registration_provisions_an_encrypted_minimum_ability_token(): void
    {
        $this->freezeTime();
        config()->set('sanctum.expiration', 1440);

        $user = app(BusinessRegistrationService::class)->create([
            'name' => 'Admin Catálogo',
            'email' => 'catalog-admin@example.com',
            'password' => 'secure-password',
            'business_trade_name' => 'Boutique Bella',
            'business_legal_name' => 'Boutique Bella Ltda.',
            'business_document' => '11222333000181',
            'business_phone' => '11999999999',
            'business_email' => 'contato@example.com',
        ]);
        $business = $user->business->fresh();
        $plainTextToken = app(CatalogCredentialService::class)->decryptToken($business);
        $accessToken = PersonalAccessToken::findToken($plainTextToken);

        $this->assertSame('boutique-bella', $business->catalog_slug);
        $this->assertNotSame($plainTextToken, $business->catalog_api_token);
        $this->assertSame(now()->addDay()->toDateTimeString(), $business->catalog_api_token_expires_at?->toDateTimeString());
        $this->assertNotNull($accessToken);
        $this->assertSame($user->id, $accessToken->tokenable_id);
        $this->assertSame(['catalog:sales:create'], $accessToken->abilities);
    }

    public function test_rotation_command_provisions_distinct_tokens_for_two_businesses(): void
    {
        $firstBusiness = Business::factory()->create(['trade_name' => 'Empresa Alfa']);
        $firstUser = app(BusinessContext::class)->run(
            $firstBusiness,
            fn (): User => User::factory()->create(),
        );
        $otherFirstBusinessUser = app(BusinessContext::class)->run(
            $firstBusiness,
            fn (): User => User::factory()->create(),
        );
        $oldToken = $otherFirstBusinessUser->createToken('catalog', ['*'])->accessToken;
        $secondBusiness = Business::factory()->create(['trade_name' => 'Empresa Beta']);
        $secondUser = app(BusinessContext::class)->run(
            $secondBusiness,
            fn (): User => User::factory()->create(),
        );

        $this->artisan('catalog:tokens-rotate-expiring', ['--all' => true])
            ->expectsOutputToContain('Credenciais rotacionadas:')
            ->assertSuccessful();

        $firstToken = app(CatalogCredentialService::class)->decryptToken($firstBusiness->fresh());
        $secondToken = app(CatalogCredentialService::class)->decryptToken($secondBusiness->fresh());
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $oldToken->id]);
        $this->assertNotSame($firstToken, $secondToken);
        $this->assertSame(['catalog:sales:create'], $firstUser->tokens()->sole()->abilities);
        $this->assertSame(['catalog:sales:create'], $secondUser->tokens()->sole()->abilities);
    }
}
