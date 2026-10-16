<?php

namespace App\Services;

use App\Models\Business;

class CatalogUrlService
{
    public function forBusiness(Business $business): ?string
    {
        $baseUrl = rtrim(trim((string) config('services.catalog.public_url')), '/');
        $slug = trim((string) $business->catalog_slug);

        if (
            $baseUrl === ''
            || preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) !== 1
        ) {
            return null;
        }

        $catalogUrl = $baseUrl.'/'.$slug;

        return $this->isPublicHttpUrl($catalogUrl)
            ? $catalogUrl
            : null;
    }

    public function whatsAppUrl(?string $catalogUrl): ?string
    {
        if ($catalogUrl === null) {
            return null;
        }

        return 'https://wa.me/?text='.rawurlencode(
            "Conheça nosso catálogo online: {$catalogUrl}",
        );
    }

    public function qrCodeUrl(?string $catalogUrl): ?string
    {
        $qrCodeServiceUrl = trim((string) config('services.catalog.qr_code_url'));

        if ($catalogUrl === null || ! $this->isPublicHttpUrl($qrCodeServiceUrl)) {
            return null;
        }

        return rtrim($qrCodeServiceUrl, '?&').'?'.http_build_query([
            'text' => $catalogUrl,
            'size' => 260,
            'margin' => 2,
            'format' => 'svg',
            'ecLevel' => 'M',
        ], '', '&', PHP_QUERY_RFC3986);
    }

    private function isPublicHttpUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false
            && in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)
            && is_string(parse_url($url, PHP_URL_HOST));
    }
}
