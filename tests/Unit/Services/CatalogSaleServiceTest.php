<?php

namespace Tests\Unit\Services;

use App\Exceptions\ExternalOrderConflictException;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Repositories\SaleRepository;
use App\Services\CatalogSaleService;
use App\Services\DefaultPersonService;
use App\Services\SaleService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Mockery;
use PDOException;
use Tests\TestCase;

class CatalogSaleServiceTest extends TestCase
{
    public function test_returns_concurrently_created_sale_after_unique_constraint_collision(): void
    {
        $externalOrderId = '0f6f6028-3df5-4a6e-851f-d5215b90c39a';
        $data = [
            'external_order_id' => $externalOrderId,
            'customer_id' => 10,
            'seller_id' => 20,
            'items' => [['product_id' => 30, 'quantity' => 2]],
        ];
        $concurrentSale = new Sale([
            'customer_id' => 10,
            'seller_id' => 20,
            'external_order_id' => $externalOrderId,
            'total' => '50.00',
        ]);
        $concurrentSale->id = 40;
        $concurrentSale->setRelation('items', new Collection([
            new SaleItem(['product_id' => 30, 'quantity' => 2]),
        ]));
        $repository = Mockery::mock(SaleRepository::class);
        $repository->shouldReceive('findByExternalOrderId')
            ->twice()
            ->with($externalOrderId)
            ->andReturn(null, $concurrentSale);
        $saleService = Mockery::mock(SaleService::class);
        $saleService->shouldReceive('create')
            ->once()
            ->with($data)
            ->andThrow(new UniqueConstraintViolationException(
                'testing',
                'insert into sales',
                [],
                new PDOException('duplicate external_order_id'),
            ));
        $service = new CatalogSaleService(
            $saleService,
            $repository,
            Mockery::mock(DefaultPersonService::class),
        );

        $result = $service->create($data);

        $this->assertSame($concurrentSale, $result['sale']);
        $this->assertFalse($result['created']);
    }

    public function test_rejects_concurrently_created_sale_with_different_content(): void
    {
        $externalOrderId = '5da34e96-808b-4248-8fb8-50c480bc6801';
        $data = [
            'external_order_id' => $externalOrderId,
            'customer_id' => 10,
            'seller_id' => 20,
            'items' => [['product_id' => 30, 'quantity' => 2]],
        ];
        $concurrentSale = new Sale([
            'customer_id' => 10,
            'seller_id' => 20,
            'external_order_id' => $externalOrderId,
            'total' => '25.00',
        ]);
        $concurrentSale->setRelation('items', new Collection([
            new SaleItem(['product_id' => 30, 'quantity' => 1]),
        ]));
        $repository = Mockery::mock(SaleRepository::class);
        $repository->shouldReceive('findByExternalOrderId')
            ->twice()
            ->with($externalOrderId)
            ->andReturn(null, $concurrentSale);
        $saleService = Mockery::mock(SaleService::class);
        $saleService->shouldReceive('create')
            ->once()
            ->with($data)
            ->andThrow(new UniqueConstraintViolationException(
                'testing',
                'insert into sales',
                [],
                new PDOException('duplicate external_order_id'),
            ));
        $service = new CatalogSaleService(
            $saleService,
            $repository,
            Mockery::mock(DefaultPersonService::class),
        );

        $this->expectException(ExternalOrderConflictException::class);

        $service->create($data);
    }
}
