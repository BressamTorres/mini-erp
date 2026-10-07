<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ProductService
{
    private const PHOTO_DIRECTORY = 'products';

    private const PHOTO_DISK = 'public';

    public function __construct(private ProductRepository $products) {}

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginate(?string $search = null, bool $showInactive = false): LengthAwarePaginator
    {
        return $this->products->paginate($search, $showInactive);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Product
    {
        $photo = $data['photo'] ?? null;
        unset($data['photo'], $data['remove_photo']);

        $photoPath = $photo instanceof UploadedFile ? $this->storePhoto($photo) : null;

        if ($photoPath !== null) {
            $data['photo_path'] = $photoPath;
        }

        try {
            return $this->products->create($data);
        } catch (Throwable $exception) {
            $this->deletePhoto($photoPath);

            throw $exception;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $productId, array $data): Product
    {
        $product = $this->products->findOrFail($productId);
        $oldPhotoPath = $product->photo_path;
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
            $updatedProduct = $this->products->update($product, $data);
        } catch (Throwable $exception) {
            $this->deletePhoto($newPhotoPath);

            throw $exception;
        }

        if (($newPhotoPath !== null || $removePhoto) && $oldPhotoPath !== null) {
            $this->deletePhoto($oldPhotoPath);
        }

        return $updatedProduct;
    }

    private function storePhoto(UploadedFile $photo): string
    {
        $path = $photo->store(self::PHOTO_DIRECTORY, self::PHOTO_DISK);

        if ($path === false) {
            throw new RuntimeException('Não foi possível armazenar a foto do produto.');
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
