<?php

namespace App\Repositories;

use App\Models\Seller;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class SellerRepository
{
    /**
     * @return LengthAwarePaginator<int, Seller>
     */
    public function paginate(?string $search = null, ?string $normalizedSearch = null): LengthAwarePaginator
    {
        return Seller::query()
            ->when($search !== null, function (Builder $query) use ($normalizedSearch, $search): void {
                $query->where(function (Builder $query) use ($normalizedSearch, $search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");

                    if ($normalizedSearch !== null) {
                        $query->orWhere('document', 'like', "%{$normalizedSearch}%")
                            ->orWhere('phone', 'like', "%{$normalizedSearch}%");
                    }
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
    public function create(array $data): Seller
    {
        return Seller::query()->create($data);
    }

    public function findOrFail(int $id): Seller
    {
        return Seller::query()->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Seller $seller, array $data): Seller
    {
        $seller->update($data);

        return $seller->refresh();
    }

    public function delete(Seller $seller): void
    {
        $seller->delete();
    }
}
