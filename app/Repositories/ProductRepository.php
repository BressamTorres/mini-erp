<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ProductRepository
{
    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginate(?string $search = null): LengthAwarePaginator
    {
        return Product::query()
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
        return Product::query()->create($data);
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

    public function delete(Product $product): void
    {
        $product->delete();
    }
}
