<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProductRepository
{
    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginate(?string $search = null, bool $showInactive = false): LengthAwarePaginator
    {
        return Product::query()
            ->when(! $showInactive, fn (Builder $query) => $query->where('active', true))
            ->when($search !== null, function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('sku', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(5)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Product
    {
        return DB::transaction(fn (): Product => Product::query()->create($data));
    }

    public function estimateNextSku(): ?string
    {
        try {
            $nextId = match (DB::connection()->getDriverName()) {
                'mysql', 'mariadb' => $this->mysqlNextId(),
                'sqlite' => $this->sqliteNextId(),
                default => null,
            };

            return $nextId === null ? null : Product::skuFromId($nextId);
        } catch (Throwable) {
            return null;
        }
    }

    public function findOrFail(int $id): Product
    {
        return Product::query()->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product
    {
        $product->update($data);

        return $product->refresh();
    }

    public function deactivate(Product $product): Product
    {
        $product->update(['active' => false]);

        return $product->refresh();
    }

    private function mysqlNextId(): ?int
    {
        $result = DB::selectOne(
            'SELECT AUTO_INCREMENT AS next_id
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [(new Product)->getTable()],
        );

        return is_numeric($result?->next_id) ? (int) $result->next_id : null;
    }

    private function sqliteNextId(): int
    {
        $result = DB::selectOne(
            'SELECT seq + 1 AS next_id FROM sqlite_sequence WHERE name = ?',
            [(new Product)->getTable()],
        );

        if (is_numeric($result?->next_id)) {
            return (int) $result->next_id;
        }

        return ((int) Product::withoutGlobalScopes()->max('id')) + 1;
    }
}
