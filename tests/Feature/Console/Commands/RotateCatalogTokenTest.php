<?php

namespace Tests\Feature\Console\Commands;

use App\Models\User;
use App\Services\CatalogCredentialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class RotateCatalogTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_rotates_catalog_token_with_minimum_ability_and_expiration(): void
    {
        $this->freezeTime();
        config()->set('sanctum.expiration', 1440);
        $user = User::factory()->create(['email' => 'admin@example.com']);
        $oldCatalogToken = $user->createToken('catalog', ['*'])->accessToken;
        $unrelatedToken = $user->createToken('mobile', ['profile:read'])->accessToken;

        $this->artisan('catalog:token-rotate', ['email' => $user->email])
            ->expectsOutputToContain('Copie o token agora.')
            ->expectsOutputToContain("Empresa: {$user->business_id}; expiração: 1440 minutos.")
            ->assertSuccessful();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $oldCatalogToken->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $unrelatedToken->id]);

        $catalogToken = $user->tokens()->where('name', 'catalog')->sole();
        $this->assertSame(['catalog:sales:create'], $catalogToken->abilities);
        $this->assertSame(now()->addDay()->toDateTimeString(), $catalogToken->expires_at?->toDateTimeString());
        $business = $user->business->fresh();
        $this->assertSame(now()->addDay()->toDateTimeString(), $business->catalog_api_token_expires_at?->toDateTimeString());
        $this->assertSame(
            $catalogToken->id,
            PersonalAccessToken::findToken(app(CatalogCredentialService::class)->decryptToken($business))?->id,
        );
    }

    public function test_does_not_issue_token_for_unknown_user(): void
    {
        $this->artisan('catalog:token-rotate', ['email' => 'unknown@example.com'])
            ->expectsOutput('Usuário empresarial não encontrado.')
            ->assertFailed();

        $this->assertDatabaseEmpty('personal_access_tokens');
    }
}
