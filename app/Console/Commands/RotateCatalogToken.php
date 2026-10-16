<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\NewAccessToken;

#[Signature('catalog:token-rotate {email : E-mail do usuário da empresa} {--name=catalog : Nome do token}')]
#[Description('Revoga o token anterior do Catálogo e emite outro com a permissão mínima')]
class RotateCatalogToken extends Command
{
    private const ABILITIES = ['catalog:sales:create'];

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $name = trim((string) $this->option('name'));
        $user = User::query()->where('email', $email)->first();

        if ($user === null || $user->business_id === null) {
            $this->error('Usuário empresarial não encontrado.');

            return self::FAILURE;
        }

        if ($name === '') {
            $this->error('O nome do token não pode ficar vazio.');

            return self::FAILURE;
        }

        $expirationMinutes = (int) config('sanctum.expiration', 43200);

        if ($expirationMinutes <= 0) {
            $this->error('SANCTUM_EXPIRATION deve ser maior que zero.');

            return self::FAILURE;
        }

        $token = DB::transaction(function () use ($user, $name, $expirationMinutes): NewAccessToken {
            $user->tokens()->where('name', $name)->delete();

            return $user->createToken(
                $name,
                self::ABILITIES,
                now()->addMinutes($expirationMinutes),
            );
        });

        $this->warn('Copie o token agora. Ele não será exibido novamente.');
        $this->line($token->plainTextToken);
        $this->info("Empresa: {$user->business_id}; expiração: {$expirationMinutes} minutos.");

        return self::SUCCESS;
    }
}
