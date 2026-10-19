<?php

namespace App\Services;

use App\Exceptions\ExternalOrderConflictException;
use App\Models\Sale;
use App\Repositories\SaleRepository;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class CatalogSaleService
{
    public function __construct(
        private SaleService $sales,
        private SaleRepository $saleRepository,
        private DefaultPersonService $defaultPeople,
        private CatalogCustomerService $catalogCustomers,
    ) {}

    /**
     * @param  array{
     *     external_order_id: string,
     *     customer_id: int|null,
     *     customer: array{name: string, document: string, phone: string, email: string}|null,
     *     seller_id: int|null,
     *     items: array<int, array{product_id: int, quantity: int}>
     * }  $data
     * @return array{sale: Sale, created: bool}
     */
    public function create(array $data): array
    {
        $resolvedData = null;

        try {
            return DB::transaction(function () use ($data, &$resolvedData): array {
                $resolvedData = $this->resolvePeople($data);
                $existingSale = $this->saleRepository->findByExternalOrderId($resolvedData['external_order_id']);

                if ($existingSale !== null) {
                    return $this->existingResult($existingSale, $resolvedData);
                }

                return [
                    'sale' => $this->sales->create($resolvedData),
                    'created' => true,
                ];
            }, attempts: 3);
        } catch (UniqueConstraintViolationException $exception) {
            if ($resolvedData === null) {
                throw $exception;
            }

            $existingSale = $this->saleRepository->findByExternalOrderId($resolvedData['external_order_id']);

            if ($existingSale === null) {
                throw $exception;
            }

            return $this->existingResult($existingSale, $resolvedData);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function resolvePeople(array $data): array
    {
        if (isset($data['customer'])) {
            $data['customer_id'] = $this->catalogCustomers->resolve($data['customer'])->id;
        }

        unset($data['customer']);

        $data['customer_id'] = $data['customer_id'] ?? $this->defaultPeople->customer()->id;
        $data['seller_id'] = $data['seller_id'] ?? $this->defaultPeople->seller()->id;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{sale: Sale, created: false}
     */
    private function existingResult(Sale $sale, array $data): array
    {
        $incomingItems = collect($data['items'])
            ->map(fn (array $item): array => [
                'product_id' => (int) $item['product_id'],
                'quantity' => (int) $item['quantity'],
            ])
            ->sortBy('product_id')
            ->values()
            ->all();
        $persistedItems = $sale->items
            ->map(fn ($item): array => [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
            ])
            ->sortBy('product_id')
            ->values()
            ->all();

        if (
            $sale->customer_id !== (int) $data['customer_id']
            || $sale->seller_id !== (int) $data['seller_id']
            || $persistedItems !== $incomingItems
        ) {
            throw new ExternalOrderConflictException;
        }

        return [
            'sale' => $sale,
            'created' => false,
        ];
    }
}
