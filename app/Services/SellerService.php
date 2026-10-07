<?php

namespace App\Services;

use App\Models\Seller;
use App\Repositories\SellerRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class SellerService
{
    private const PHOTO_DIRECTORY = 'sellers';

    private const PHOTO_DISK = 'public';

    public function __construct(private SellerRepository $sellers) {}

    /**
     * @return LengthAwarePaginator<int, Seller>
     */
    public function paginate(?string $search = null): LengthAwarePaginator
    {
        $normalizedSearch = $search !== null && preg_match('/^[\d\s().\/-]+$/', $search) === 1
            ? preg_replace('/\D+/', '', $search)
            : null;

        return $this->sellers->paginate($search, $normalizedSearch ?: null);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Seller
    {
        $photo = $data['photo'] ?? null;
        unset($data['photo'], $data['remove_photo']);

        $photoPath = $photo instanceof UploadedFile ? $this->storePhoto($photo) : null;

        if ($photoPath !== null) {
            $data['photo_path'] = $photoPath;
        }

        try {
            return $this->sellers->create($data);
        } catch (Throwable $exception) {
            $this->deletePhoto($photoPath);

            throw $exception;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $sellerId, array $data): Seller
    {
        $seller = $this->sellers->findOrFail($sellerId);
        $oldPhotoPath = $seller->photo_path;
        $photo = $data['photo'] ?? null;
        $removePhoto = (bool) ($data['remove_photo'] ?? false);
        unset($data['photo'], $data['remove_photo']);

        $newPhotoPath = $photo instanceof UploadedFile ? $this->storePhoto($photo) : null;

        if ($newPhotoPath !== null) {
            $data['photo_path'] = $newPhotoPath;
        } elseif ($removePhoto) {
            $data['photo_path'] = null;
        }

        try {
            $updatedSeller = $this->sellers->update($seller, $data);
        } catch (Throwable $exception) {
            $this->deletePhoto($newPhotoPath);

            throw $exception;
        }

        if (($newPhotoPath !== null || $removePhoto) && $oldPhotoPath !== null) {
            $this->deletePhoto($oldPhotoPath);
        }

        return $updatedSeller;
    }

    public function destroy(int $sellerId): void
    {
        $seller = $this->sellers->findOrFail($sellerId);
        $photoPath = $seller->photo_path;

        $this->sellers->delete($seller);
        $this->deletePhoto($photoPath);
    }

    private function storePhoto(UploadedFile $photo): string
    {
        $path = $photo->store(self::PHOTO_DIRECTORY, self::PHOTO_DISK);

        if ($path === false) {
            throw new RuntimeException('Não foi possível armazenar a foto do vendedor.');
        }

        return $path;
    }

    private function deletePhoto(?string $path): void
    {
        if ($path !== null) {
            Storage::disk(self::PHOTO_DISK)->delete($path);
        }
    }
}
