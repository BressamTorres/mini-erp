<?php

namespace App\Services;

use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;

class FormSubmissionService
{
    private const COMPLETED_TOKEN_TTL_SECONDS = 86400;

    private const FALLBACK_TTL_SECONDS = 30;

    private const LOCK_SECONDS = 120;

    private const WAIT_SECONDS = 30;

    /** @param array<string, mixed> $payload */
    public function run(
        string $scope,
        int $userId,
        ?string $token,
        array $payload,
        Closure $operation,
    ): bool {
        $token = is_string($token) ? trim($token) : '';
        $usesClientToken = $token !== '';
        $identifier = $usesClientToken ? $token : $this->fingerprint($payload);
        $key = hash('sha256', "{$userId}|{$scope}|{$identifier}");
        $completedKey = "form-submission:completed:{$key}";

        try {
            return Cache::lock("form-submission:lock:{$key}", self::LOCK_SECONDS)
                ->block(self::WAIT_SECONDS, function () use ($completedKey, $operation, $usesClientToken): bool {
                    if (Cache::has($completedKey)) {
                        return false;
                    }

                    $operation();

                    Cache::put(
                        $completedKey,
                        true,
                        $usesClientToken
                            ? self::COMPLETED_TOKEN_TTL_SECONDS
                            : self::FALLBACK_TTL_SECONDS,
                    );

                    return true;
                });
        } catch (LockTimeoutException) {
            return false;
        }
    }

    /** @param array<string, mixed> $payload */
    private function fingerprint(array $payload): string
    {
        return hash('sha256', json_encode($this->normalize($payload), JSON_THROW_ON_ERROR));
    }

    private function normalize(mixed $value): mixed
    {
        if ($value instanceof UploadedFile) {
            return [
                'hash' => hash_file('sha256', $value->getRealPath()),
                'name' => $value->getClientOriginalName(),
                'size' => $value->getSize(),
            ];
        }

        if (! is_array($value)) {
            return $value;
        }

        if (! array_is_list($value)) {
            ksort($value);
        }

        return array_map(fn (mixed $item): mixed => $this->normalize($item), $value);
    }
}
