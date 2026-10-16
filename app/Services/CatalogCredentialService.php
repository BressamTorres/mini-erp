<?php

namespace App\Services;

use App\Models\Business;
use App\Models\User;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;
use RuntimeException;

class CatalogCredentialService
{
    public const TOKEN_NAME = 'catalog';

    public const ABILITIES = ['catalog:sales:create'];

    public function rotate(User $user): NewAccessToken
    {
        $business = $user->business;

        if ($business === null) {
            throw new RuntimeException('Usuário empresarial não encontrado.');
        }

        $expirationMinutes = (int) config('sanctum.expiration', 43200);

        if ($expirationMinutes <= 0) {
            throw new RuntimeException('SANCTUM_EXPIRATION deve ser maior que zero.');
        }

        return DB::transaction(function () use ($business, $user, $expirationMinutes): NewAccessToken {
            PersonalAccessToken::query()
                ->where('name', self::TOKEN_NAME)
                ->where('tokenable_type', $user->getMorphClass())
                ->whereIn('tokenable_id', $business->users()->select('id'))
                ->delete();

            $token = $user->createToken(
                self::TOKEN_NAME,
                self::ABILITIES,
                now()->addMinutes($expirationMinutes),
            );

            $business->forceFill([
                'catalog_api_token' => $this->encrypter()->encryptString($token->plainTextToken),
                'catalog_api_token_expires_at' => now()->addMinutes($expirationMinutes),
            ])->save();

            return $token;
        });
    }

    public function decryptToken(Business $business): string
    {
        $encryptedToken = (string) $business->catalog_api_token;

        if ($encryptedToken === '') {
            throw new RuntimeException('A empresa não possui credencial do Catálogo.');
        }

        return $this->encrypter()->decryptString($encryptedToken);
    }

    private function encrypter(): Encrypter
    {
        $configuredKey = (string) config('services.catalog.credentials_key');
        $key = str_starts_with($configuredKey, 'base64:')
            ? base64_decode(substr($configuredKey, 7), true)
            : $configuredKey;

        if (! is_string($key) || ! Encrypter::supported($key, 'AES-256-CBC')) {
            throw new RuntimeException('CATALOG_CREDENTIAL_KEY deve possuir 32 bytes e usar o formato base64: recomendado.');
        }

        return new Encrypter($key, 'AES-256-CBC');
    }
}
