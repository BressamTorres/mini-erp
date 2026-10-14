<?php

namespace App\Services;

use App\Models\Person;
use App\Repositories\PersonRepository;
use App\Repositories\PhotoRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class PersonService
{
    private const PHOTO_DIRECTORY = 'persons';

    public function __construct(
        private PersonRepository $persons,
        private PhotoRepository $photos,
    ) {}

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
        $data['active'] = true;

        $photoPath = $photo instanceof UploadedFile ? $this->storePhoto($photo) : null;

        if ($photoPath !== null) {
            $data['photo_path'] = $photoPath;
            $data['photo_disk'] = $this->photoDisk();
        }

        try {
            return $this->persons->create($type, $data);
        } catch (Throwable $exception) {
            $this->deletePhoto($photoPath, $this->photoDisk());

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
        $oldPhotoDisk = $person->storedPhotoDisk();
        $photo = $data['photo'] ?? null;
        $removePhoto = (bool) ($data['remove_photo'] ?? false);
        unset($data['photo'], $data['remove_photo'], $data['type']);

        $newPhotoPath = $photo instanceof UploadedFile ? $this->storePhoto($photo) : null;

        if ($newPhotoPath !== null) {
            $data['photo_path'] = $newPhotoPath;
            $data['photo_disk'] = $this->photoDisk();
        } elseif ($removePhoto) {
            $data['photo_path'] = null;
            $data['photo_disk'] = null;
        }

        try {
            $updatedPerson = $this->persons->update($person, $data);
        } catch (Throwable $exception) {
            $this->deletePhoto($newPhotoPath, $this->photoDisk());

            throw $exception;
        }

        if (($newPhotoPath !== null || $removePhoto) && $oldPhotoPath !== null) {
            $this->deletePhoto($oldPhotoPath, $oldPhotoDisk);
        }

        return $updatedPerson;
    }

    public function deactivate(string $type, int $personId): Person
    {
        $this->ensureValidType($type);

        return $this->persons->deactivate(
            $this->persons->findOrFailByType($personId, $type),
        );
    }

    public function photo(int $personId): StreamedResponse
    {
        $person = $this->persons->findOrFail($personId);

        abort_if($person->photo_path === null, 404);

        $disk = Storage::disk($person->storedPhotoDisk());

        abort_unless($disk->exists($person->photo_path), 404);

        return $disk->response($person->photo_path, null, [
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function storePhoto(UploadedFile $photo): string
    {
        $path = $photo->store(self::PHOTO_DIRECTORY, $this->photoDisk());

        if ($path === false) {
            throw new RuntimeException('Não foi possível armazenar a foto da pessoa.');
        }

        return $path;
    }

    private function deletePhoto(?string $path, string $disk): void
    {
        if ($path !== null && ! $this->photos->isReferenced($path, $disk)) {
            Storage::disk($disk)->delete($path);
        }
    }

    private function photoDisk(): string
    {
        return (string) config('filesystems.person_photo_disk', 'public');
    }

    private function ensureValidType(string $type): void
    {
        if (! in_array($type, Person::TYPES, true)) {
            throw new InvalidArgumentException('Tipo de pessoa inválido.');
        }
    }
}
