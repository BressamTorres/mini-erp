<?php

namespace Tests\Feature\MySql;

use App\Models\Business;
use App\Models\Product;
use App\Repositories\ProductRepository;
use App\Services\BusinessContext;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;

#[Group('mysql')]
class ProductSkuCreationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (
            config('database.default') !== 'mysql'
            || config('database.connections.mysql.host') !== '127.0.0.1'
            || (int) config('database.connections.mysql.port') !== 33060
            || DB::getDatabaseName() !== 'mini_erp_sku_test'
        ) {
            throw new RuntimeException('O teste MySQL só pode usar o banco local descartável mini_erp_sku_test.');
        }

        $this->artisan('migrate:fresh', ['--force' => true])->assertSuccessful();
    }

    protected function tearDown(): void
    {
        if ($this->app?->bound(BusinessContext::class)) {
            app(BusinessContext::class)->clear();
        }

        parent::tearDown();
    }

    public function test_nullable_sku_migration_fixes_mysql_creation_without_losing_uniqueness(): void
    {
        $this->artisan('migrate:rollback', ['--step' => 1, '--force' => true])->assertSuccessful();
        $this->assertSame('NO', $this->skuIsNullable());

        $business = Business::factory()->create();
        app(BusinessContext::class)->set($business);

        try {
            app(ProductRepository::class)->create([
                'name' => 'Produto com schema antigo',
                'sale_price' => '10.00',
            ]);

            $this->fail('O MySQL deveria rejeitar o INSERT com SKU nulo no schema antigo.');
        } catch (QueryException $exception) {
            $this->assertSame(1048, $exception->errorInfo[1] ?? null);
        }

        $this->assertDatabaseEmpty('products');

        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        $this->assertSame('YES', $this->skuIsNullable());

        $skuPreview = app(ProductRepository::class)->estimateNextSku();
        $this->assertNotNull($skuPreview);
        $this->assertSame($skuPreview, app(ProductRepository::class)->estimateNextSku());

        $product = app(ProductRepository::class)->create([
            'name' => 'Produto com SKU automático',
            'sale_price' => '10.00',
        ]);

        $this->assertSame($skuPreview, $product->sku);
        $this->assertSame(sprintf('%06d', $product->id), $product->sku);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'business_id' => $business->id,
            'sku' => $product->sku,
        ]);
        $this->assertSame(0, Product::query()->whereNull('sku')->count());

        try {
            DB::table('products')->insert([
                'business_id' => $business->id,
                'sku' => $product->sku,
                'name' => 'Produto duplicado',
                'sale_price' => '20.00',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->fail('O índice único deveria rejeitar o SKU duplicado na mesma empresa.');
        } catch (QueryException $exception) {
            $this->assertSame(1062, $exception->errorInfo[1] ?? null);
        }

        $this->assertDatabaseCount('products', 1);
    }

    private function skuIsNullable(): string
    {
        $column = DB::selectOne(
            'SELECT IS_NULLABLE AS is_nullable
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [DB::getDatabaseName(), 'products', 'sku'],
        );

        return $column->is_nullable;
    }
}
