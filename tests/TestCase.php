<?php

namespace Tests;

use App\Models\Business;
use App\Services\BusinessContext;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (
            config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.database') !== ':memory:'
        ) {
            throw new RuntimeException('Os testes devem utilizar exclusivamente SQLite em memória.');
        }

        if (Schema::hasTable('businesses')) {
            app(BusinessContext::class)->set(Business::factory()->create());
        }
    }

    protected function tearDown(): void
    {
        if ($this->app?->bound(BusinessContext::class)) {
            app(BusinessContext::class)->clear();
        }

        parent::tearDown();
    }

    protected function fakeImage(string $name = 'photo.png'): File
    {
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9Zl8sAAAAASUVORK5CYII=',
            true,
        );

        return UploadedFile::fake()->createWithContent($name, $png);
    }
}
