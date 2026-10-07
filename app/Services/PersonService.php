<?php

namespace App\Services;

use App\Models\Person;
use App\Repositories\PersonRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class PersonService
{
    private const PHOTO_DIRECTORY = 'persons';

    private const PHOTO_DISK = 'public';

    public function __construct(private PersonRepository $persons) {}

    /**
     * @return LengthAwarePaginator<int, Person>
     */
    public function paginate(string $type, ?string $search = null, bool $showInactive = false): LengthAwarePaginator
    {
        $this->ensureValidType($type);

        $normalizedSearch = $search !== null && preg_match('/^[\d\s().\/-]+$/', $search) === 1
            ? preg_replace('/\D+/', '', $search)
            : null;

        return $this->persons->paginate($type, $search, $normalizedSearch ?: null, $showInactive);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(string $type, array $data): Person
    {
        $this->ensureValidType($type);

        $photo = $data['photo'] ?? null;
        unset($data['photo'], $data['remove_photo'], $data['type']);

        $photoPath = $photo instanceof UploadedFile ? $this->storePhoto($photo) : null;

        if ($photoPath !== null) {
            $data['photo_path'] = $photoPath;
        }

        try {
            return $this->persons->create($type, $data);
        } catch (Throwable $exception) {
            $this->deletePhoto($photoPath);

            throw $exception;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $type, int $personId, array $data): Person
    {
        $this->ensureValidType($type);

        $person = $this->persons->findOrFailByType($personId, $type);
        $oldPhotoPath = $person->photo_path;
        $photo = $data['photo'] ?? null;
        $removePhoto = (bool) ($data['remove_photo'] ?? false);
        unset($data['photo'], $data['remove_photo'], $data['type']);

        $newPhotoPath = $photo instanceof UploadedFile ? $this->storePhoto($photo) : null;

        if ($newPhotoPath !== null) {
            $data['photo_path'] = $newPhotoPath;
        } elseif ($removePhoto) {
            $data['photo_path'] = null;
        }

        try {
            $updatedPerson = $this->persons->update($person, $data);
        } catch (Throwable $exception) {
            $this->deletePhoto($newPhotoPath);

            throw $exception;
        }

        if (($newPhotoPath !== null || $removePhoto) && $oldPhotoPath !== null) {
            $this->deletePhoto($oldPhotoPath);
        }

        return $updatedPerson;
    }

    private function storePhoto(UploadedFile $photo): string
    {
        $path = $photo->store(self::PHOTO_DIRECTORY, self::PHOTO_DISK);

        if ($path === false) {
            throw new RuntimeException('Não foi possível armazenar a foto da pessoa.');
        }

        return $path;
    }

    private function deletePhoto(?string $path): void
    {
        if ($path !== null) {
            Storage::disk(self::PHOTO_DISK)->delete($path);
        }
    }

    private function ensureValidType(string $type): void
    {
        if (! in_array($type, Person::TYPES, true)) {
            throw new InvalidArgumentException('Tipo de pessoa inválido.');
        }
    }
}
