<?php

namespace App\Repositories;

use App\Models\Person;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class SaleRepository
{
    /**
     * @return LengthAwarePaginator<int, Sale>
     */
    public function paginate(?string $search = null, bool $showInactive = false): LengthAwarePaginator
    {
        return Sale::query()
            ->with([
                'customer:id,name',
                'seller:id,name',
            ])
            ->withSum('items as items_quantity', 'quantity')
            ->when(! $showInactive, fn (Builder $query) => $query->where('active', true))
            ->when($search !== null, function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->whereHas('customer', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('seller', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"));

                    if (preg_match('/^#?0*(\d+)$/', $search, $matches) === 1) {
                        $query->orWhereKey((int) $matches[1]);
                    }
                });
            })
            ->latest('id')
            ->paginate(5)
            ->withQueryString();
    }

    public function findForDetailsOrFail(int $id): Sale
    {
        return Sale::query()
            ->with([
                'customer:id,name,active',
                'seller:id,name,active',
                'items.product:id,sku,name,sale_price,photo_path,active',
            ])
            ->findOrFail($id);
    }

    /** @return Collection<int, Person> */
    public function activePeople(string $type): Collection
    {
        return Person::query()
            ->where('type', $type)
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'active']);
    }

    /** @return Collection<int, Product> */
    public function activeProducts(): Collection
    {
        return Product::query()
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'sku', 'name', 'sale_price', 'photo_path', 'active']);
    }

    public function findPersonForUpdate(int $id, string $type): ?Person
    {
        return Person::query()
            ->where('type', $type)
            ->lockForUpdate()
            ->find($id);
    }

    /** @param array<int, int> $ids
     * @return Collection<int, Product>
     */
    public function findProductsForUpdate(array $ids): Collection
    {
        return Product::query()
            ->whereIn('id', $ids)
            ->lockForUpdate()
            ->get();
    }

    public function findForUpdateOrFail(int $id): Sale
    {
        return Sale::query()
            ->with('items')
            ->lockForUpdate()
            ->findOrFail($id);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): Sale
    {
        return Sale::query()->create($data);
    }

    /** @param array<string, mixed> $data */
    public function update(Sale $sale, array $data): Sale
    {
        $sale->update($data);

        return $sale;
    }

    public function deactivate(Sale $sale): Sale
    {
        $sale->update(['active' => false]);

        return $sale->refresh();
    }

    /** @param array<int, array<string, mixed>> $items */
    public function replaceItems(Sale $sale, array $items): void
    {
        $sale->items()->delete();
        $sale->items()->createMany($items);
    }
}
