<?php

namespace App\Repositories;

use App\Models\Person;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class PersonRepository
{
    /**
     * @return LengthAwarePaginator<int, Person>
     */
    public function paginate(string $type, ?string $search = null, ?string $normalizedSearch = null, bool $showInactive = false): LengthAwarePaginator
    {
        return Person::query()
            ->where('type', $type)
            ->when(! $showInactive, fn (Builder $query) => $query->where('active', true))
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
    public function create(string $type, array $data): Person
    {
        $data['type'] = $type;

        return Person::query()->create($data);
    }

    public function findOrFailByType(int $id, string $type): Person
    {
        return Person::query()
            ->where('type', $type)
            ->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Person $person, array $data): Person
    {
        unset($data['type']);
        $person->update($data);

        return $person->refresh();
    }

    public function deactivate(Person $person): Person
    {
        $person->update(['active' => false]);

        return $person->refresh();
    }
}
