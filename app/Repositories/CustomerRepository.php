<?php

namespace App\Repositories;

use App\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class CustomerRepository
{
    /**
     * @return LengthAwarePaginator<int, Customer>
     */
    public function paginate(?string $search = null, ?string $normalizedSearch = null): LengthAwarePaginator
    {
        return Customer::query()
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
    public function create(array $data): Customer
    {
        return Customer::query()->create($data);
    }

    public function findOrFail(int $id): Customer
    {
        return Customer::query()->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Customer $customer, array $data): Customer
    {
        $customer->update($data);

        return $customer->refresh();
    }

    public function delete(Customer $customer): void
    {
        $customer->delete();
    }
}
