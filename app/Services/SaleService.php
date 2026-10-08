<?php

namespace App\Services;

use App\Models\Person;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Repositories\SaleRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function __construct(private SaleRepository $sales) {}

    /**
     * @return LengthAwarePaginator<int, Sale>
     */
    public function paginate(?string $search = null, bool $showInactive = false): LengthAwarePaginator
    {
        return $this->sales->paginate($search, $showInactive);
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    public function formOptions(): array
    {
        return [
            'customers' => $this->sales->activePeople(Person::TYPE_CUSTOMER)
                ->map(fn (Person $person): array => $this->personOption($person))
                ->all(),
            'sellers' => $this->sales->activePeople(Person::TYPE_SELLER)
                ->map(fn (Person $person): array => $this->personOption($person))
                ->all(),
            'products' => $this->sales->activeProducts()
                ->map(fn (Product $product): array => $this->productOption($product))
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Sale
    {
        return DB::transaction(function () use ($data): Sale {
            $this->eligiblePerson((int) $data['customer_id'], Person::TYPE_CUSTOMER, 'customer_id');
            $this->eligiblePerson((int) $data['seller_id'], Person::TYPE_SELLER, 'seller_id');

            $items = $this->prepareItems($data['items']);
            $sale = $this->sales->create([
                'customer_id' => $data['customer_id'],
                'seller_id' => $data['seller_id'],
                'total' => $this->sumItems($items),
                'active' => true,
            ]);
            $this->sales->replaceItems($sale, $items);

            return $sale->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $saleId, array $data): Sale
    {
        return DB::transaction(function () use ($data, $saleId): Sale {
            $sale = $this->sales->findForUpdateOrFail($saleId);
            $this->eligiblePerson((int) $data['customer_id'], Person::TYPE_CUSTOMER, 'customer_id', $sale->customer_id);
            $this->eligiblePerson((int) $data['seller_id'], Person::TYPE_SELLER, 'seller_id', $sale->seller_id);

            $historicalItems = $sale->items->keyBy('id')->all();
            $items = $this->prepareItems($data['items'], $historicalItems);

            $this->sales->update($sale, [
                'customer_id' => $data['customer_id'],
                'seller_id' => $data['seller_id'],
                'total' => $this->sumItems($items),
                'active' => $data['active'],
            ]);
            $this->sales->replaceItems($sale, $items);

            return $sale->refresh();
        });
    }

    public function deactivate(int $saleId): Sale
    {
        return DB::transaction(fn (): Sale => $this->sales->deactivate(
            $this->sales->findForUpdateOrFail($saleId),
        ));
    }

    /**
     * @return array<string, mixed>
     */
    public function details(int $saleId): array
    {
        $sale = $this->sales->findForDetailsOrFail($saleId);

        return [
            'id' => $sale->id,
            'number' => $sale->formattedNumber(),
            'date' => $sale->created_at->format('d/m/Y H:i'),
            'active' => $sale->active,
            'status' => $sale->active ? 'Ativo' : 'Inativo',
            'customer' => $this->personOption($sale->customer),
            'seller' => $this->personOption($sale->seller),
            'total' => $sale->formattedTotal(),
            'items' => $sale->items->map(fn ($item): array => [
                'sale_item_id' => $item->id,
                'product_id' => $item->product_id,
                'product' => $item->product->name,
                'sku' => $item->product->sku,
                'photo_url' => $item->product->photoUrl(),
                'product_active' => $item->product->active,
                'quantity' => $item->quantity,
                'unit_price_value' => $item->unit_price,
                'unit_price' => $item->formattedUnitPrice(),
                'total' => $item->formattedTotal(),
            ])->all(),
        ];
    }

    private function eligiblePerson(int $id, string $type, string $field, ?int $currentId = null): Person
    {
        $person = $this->sales->findPersonForUpdate($id, $type);

        if ($person === null || (! $person->active && $person->id !== $currentId)) {
            throw ValidationException::withMessages([
                $field => $type === Person::TYPE_CUSTOMER
                    ? 'Selecione um cliente ativo válido.'
                    : 'Selecione um vendedor ativo válido.',
            ]);
        }

        return $person;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rawItems
     * @param  array<int, SaleItem>  $historicalItems
     * @return array<int, array<string, mixed>>
     */
    private function prepareItems(array $rawItems, array $historicalItems = []): array
    {
        $productIds = collect($rawItems)->pluck('product_id')->map(fn ($id): int => (int) $id)->all();
        $products = $this->sales->findProductsForUpdate($productIds)->keyBy('id');
        $items = [];

        foreach ($rawItems as $index => $rawItem) {
            $productId = (int) $rawItem['product_id'];
            $product = $products->get($productId);
            $saleItemId = isset($rawItem['sale_item_id']) ? (int) $rawItem['sale_item_id'] : null;
            $historicalItem = $saleItemId !== null ? ($historicalItems[$saleItemId] ?? null) : null;
            $isExisting = $historicalItem !== null && $historicalItem->product_id === $productId;

            if ($saleItemId !== null && ! $isExisting) {
                throw ValidationException::withMessages([
                    "items.{$index}.product_id" => 'O produto não corresponde ao item histórico informado.',
                ]);
            }

            if ($product === null || (! $product->active && ! $isExisting)) {
                throw ValidationException::withMessages([
                    "items.{$index}.product_id" => 'Selecione um produto ativo válido.',
                ]);
            }

            $quantity = (int) $rawItem['quantity'];
            $unitPrice = $isExisting ? $historicalItem->unit_price : $product->sale_price;
            $unitPriceCents = $this->decimalToCents($unitPrice);

            $items[] = [
                'product_id' => $productId,
                'quantity' => $quantity,
                'unit_price' => $this->centsToDecimal($unitPriceCents),
                'total' => $this->centsToDecimal($unitPriceCents * $quantity),
            ];
        }

        return $items;
    }

    /** @param array<int, array<string, mixed>> $items */
    private function sumItems(array $items): string
    {
        $total = array_sum(array_map(
            fn (array $item): int => $this->decimalToCents($item['total']),
            $items,
        ));

        return $this->centsToDecimal($total);
    }

    private function decimalToCents(string|int|float $value): int
    {
        $value = (string) $value;
        [$integer, $fraction] = array_pad(explode('.', $value, 2), 2, '');

        return ((int) $integer * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }

    private function centsToDecimal(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    /** @return array<string, mixed> */
    private function personOption(Person $person): array
    {
        return [
            'id' => $person->id,
            'name' => $person->name,
            'active' => $person->active,
        ];
    }

    /** @return array<string, mixed> */
    private function productOption(Product $product): array
    {
        return [
            'id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'sale_price' => $product->sale_price,
            'photo_url' => $product->photoUrl(),
            'active' => $product->active,
        ];
    }
}
