<?php

namespace App\Services;

class UploadLimitService
{
    private const MULTIPART_OVERHEAD_BYTES = 262_144;

    public function maxFileBytes(): ?int
    {
        $uploadLimit = $this->parseIniSize(ini_get('upload_max_filesize'));
        $postLimit = $this->parseIniSize(ini_get('post_max_size'));
        $proxyLimit = config('uploads.proxy_max_bytes');

        $limits = array_filter([
            $uploadLimit,
            $postLimit === null ? null : max(1, $postLimit - self::MULTIPART_OVERHEAD_BYTES),
            is_numeric($proxyLimit) && (int) $proxyLimit > 0
                ? max(1, (int) $proxyLimit - self::MULTIPART_OVERHEAD_BYTES)
                : null,
        ], fn (?int $limit): bool => $limit !== null && $limit > 0);

        return $limits === [] ? null : min($limits);
    }

    private function parseIniSize(string|false $value): ?int
    {
        if ($value === false || trim($value) === '') {
            return null;
        }

        $bytes = ini_parse_quantity($value);

        return $bytes > 0 ? $bytes : null;
    }
}
