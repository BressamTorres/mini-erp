<?php

namespace App\Repositories;

use App\Models\Person;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

class PhotoRepository
{
    public function isReferenced(string $path, string $disk): bool
    {
        return $this->isReferencedBy(Person::query(), $path, $disk)
            || $this->isReferencedBy(Product::query(), $path, $disk);
    }

    /** @param Builder<Person|Product> $query */
    private function isReferencedBy(Builder $query, string $path, string $disk): bool
    {
        $legacyDisk = (string) config('filesystems.legacy_photo_disk', 'public');

        return $query
            ->where('photo_path', $path)
            ->where(function (Builder $query) use ($disk, $legacyDisk): void {
                $query->where('photo_disk', $disk);

                if ($disk === $legacyDisk) {
                    $query->orWhereNull('photo_disk');
                }
            })
            ->exists();
    }
}
