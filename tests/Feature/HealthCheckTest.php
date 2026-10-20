<?php

namespace Tests\Feature;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_up_healthcheck_is_public_and_independent_from_database_and_integrations(): void
    {
        DB::listen(static function (QueryExecuted $query): never {
            throw new RuntimeException("O healthcheck executou SQL: {$query->sql}");
        });
        Http::preventStrayRequests();

        $this->getJson('/up')
            ->assertOk()
            ->assertExactJson(['status' => 'up']);
    }
}
