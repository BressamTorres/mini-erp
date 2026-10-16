<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\CatalogCredentialService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('catalog:token-rotate {email : E-mail do usuário da empresa} {--name=catalog : Nome do token}')]
#[Description('Revoga o token anterior do Catálogo e emite outro com a permissão mínima')]
class RotateCatalogToken extends Command
{
    public function __construct(private CatalogCredentialService $catalogCredentials)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $user = User::query()->where('email', $email)->first();

        if ($user === null || $user->business_id === null) {
            $this->error('Usuário empresarial não encontrado.');

            return self::FAILURE;
        }

        if (trim((string) $this->option('name')) !== CatalogCredentialService::TOKEN_NAME) {
            $this->error('O token integrado deve utilizar o nome catalog.');

            return self::FAILURE;
        }

        $expirationMinutes = (int) config('sanctum.expiration', 43200);

        if ($expirationMinutes <= 0) {
            $this->error('SANCTUM_EXPIRATION deve ser maior que zero.');

            return self::FAILURE;
        }

        $token = $this->catalogCredentials->rotate($user);

        $this->warn('Copie o token agora. Ele não será exibido novamente.');
        $this->line($token->plainTextToken);
        $this->info("Empresa: {$user->business_id}; expiração: {$expirationMinutes} minutos.");

        return self::SUCCESS;
    }
}
