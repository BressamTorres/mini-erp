<?php

namespace App\Services;

use App\Models\Customer;
use App\Repositories\CustomerRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class CustomerService
{
    private const PHOTO_DIRECTORY = 'customers';

    private const PHOTO_DISK = 'public';

    public function __construct(private CustomerRepository $customers) {}

    /**
     * @return LengthAwarePaginator<int, Customer>
     */
    public function paginate(?string $search = null): LengthAwarePaginator
    {
        $normalizedSearch = $search !== null && preg_match('/^[\d\s().\/-]+$/', $search) === 1
            ? preg_replace('/\D+/', '', $search)
            : null;

        return $this->customers->paginate($search, $normalizedSearch ?: null);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Customer
    {
        $photo = $data['photo'] ?? null;
        unset($data['photo'], $data['remove_photo']);

        $photoPath = $photo instanceof UploadedFile
            ? $this->storePhoto($photo)
            : null;

        if ($photoPath !== null) {
            $data['photo_path'] = $photoPath;
        }

        try {
            return $this->customers->create($data);
        } catch (Throwable $exception) {
            $this->deletePhoto($photoPath);

            throw $exception;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $customerId, array $data): Customer
    {
        $customer = $this->customers->findOrFail($customerId);
        $oldPhotoPath = $customer->photo_path;
        $photo = $data['photo'] ?? null;
        $removePhoto = (bool) ($data['remove_photo'] ?? false);
        unset($data['photo'], $data['remove_photo']);

        $newPhotoPath = $photo instanceof UploadedFile
            ? $this->storePhoto($photo)
            : null;

        if ($newPhotoPath !== null) {
            $data['photo_path'] = $newPhotoPath;
        } elseif ($removePhoto) {
            $data['photo_path'] = null;
        }

        try {
            $updatedCustomer = $this->customers->update($customer, $data);
        } catch (Throwable $exception) {
            $this->deletePhoto($newPhotoPath);

            throw $exception;
        }

        if (($newPhotoPath !== null || $removePhoto) && $oldPhotoPath !== null) {
            $this->deletePhoto($oldPhotoPath);
        }

        return $updatedCustomer;
    }

    public function destroy(int $customerId): void
    {
        $customer = $this->customers->findOrFail($customerId);
        $photoPath = $customer->photo_path;

        $this->customers->delete($customer);
        $this->deletePhoto($photoPath);
    }

    private function storePhoto(UploadedFile $photo): string
    {
        $path = $photo->store(self::PHOTO_DIRECTORY, self::PHOTO_DISK);

        if ($path === false) {
            throw new RuntimeException('Não foi possível armazenar a foto do cliente.');
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
