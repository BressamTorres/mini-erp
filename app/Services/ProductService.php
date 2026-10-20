<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\PhotoRepository;
use App\Repositories\ProductRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ProductService
{
    public function __construct(
        private ProductRepository $products,
        private PhotoRepository $photos,
    ) {}

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginate(?string $search = null, bool $showInactive = false): LengthAwarePaginator
    {
        return $this->products->paginate($search, $showInactive);
    }

    public function estimateNextSku(): ?string
    {
        return $this->products->estimateNextSku();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Product
    {
        $photo = $data['photo'] ?? null;
        unset($data['photo'], $data['remove_photo'], $data['sku']);
        $data['active'] = true;

        $photoPath = $photo instanceof UploadedFile ? $this->storePhoto($photo) : null;

        if ($photoPath !== null) {
            $data['photo_path'] = $photoPath;
            $data['photo_disk'] = $this->photoDisk();
        }

        try {
            return $this->products->create($data);
        } catch (Throwable $exception) {
            $this->deletePhoto($photoPath, $this->photoDisk());

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
        $oldPhotoDisk = $product->storedPhotoDisk();
        $photo = $data['photo'] ?? null;
        $removePhoto = (bool) ($data['remove_photo'] ?? false);
        unset($data['photo'], $data['remove_photo'], $data['sku']);

        $newPhotoPath = $photo instanceof UploadedFile ? $this->storePhoto($photo) : null;

        if ($newPhotoPath !== null) {
            $data['photo_path'] = $newPhotoPath;
            $data['photo_disk'] = $this->photoDisk();
        } elseif ($removePhoto) {
            $data['photo_path'] = null;
            $data['photo_disk'] = null;
        }

        try {
            $updatedProduct = $this->products->update($product, $data);
        } catch (Throwable $exception) {
            $this->deletePhoto($newPhotoPath, $this->photoDisk());

            throw $exception;
        }

        if (($newPhotoPath !== null || $removePhoto) && $oldPhotoPath !== null) {
            $this->deletePhoto($oldPhotoPath, $oldPhotoDisk);
        }

        return $updatedProduct;
    }

    public function deactivate(int $productId): Product
    {
        return $this->products->deactivate(
            $this->products->findOrFail($productId),
        );
    }

    private function storePhoto(UploadedFile $photo): string
    {
        $path = $photo->store($this->photoDirectory(), $this->photoDisk());

        if ($path === false) {
            throw new RuntimeException('Não foi possível armazenar a foto do produto.');
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
        return (string) config('filesystems.product_photo_disk', 'public');
    }

    private function photoDirectory(): string
    {
        return 'businesses/'.app(BusinessContext::class)->id().'/products';
    }
}
