<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Services\CatalogCredentialService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('catalog:tokens-rotate-expiring {--days=7 : Antecedência mínima para rotação} {--all : Rotaciona todas as empresas}')]
#[Description('Rotaciona credenciais Sanctum dos catálogos antes da expiração')]
class RotateExpiringCatalogTokens extends Command
{
    public function __construct(private CatalogCredentialService $catalogCredentials)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $days = (int) $this->option('days');

        if ($days < 0) {
            $this->error('A antecedência deve ser zero ou maior.');

            return self::FAILURE;
        }

        $query = Business::query()->orderBy('id');

        if (! $this->option('all')) {
            $query->where(function ($query) use ($days): void {
                $query->whereNull('catalog_api_token')
                    ->orWhereNull('catalog_api_token_expires_at')
                    ->orWhere('catalog_api_token_expires_at', '<=', now()->addDays($days));
            });
        }

        $rotated = 0;
        $skipped = 0;

        $query->eachById(function (Business $business) use (&$rotated, &$skipped): void {
            $user = $business->users()->oldest('id')->first();

            if ($user === null) {
                $skipped++;

                return;
            }

            $this->catalogCredentials->rotate($user);
            $rotated++;
        });

        $this->info("Credenciais rotacionadas: {$rotated}; empresas ignoradas: {$skipped}.");

        return self::SUCCESS;
    }
}
