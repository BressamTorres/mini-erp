<?php

namespace App\Services;

use App\Models\Business;
use Illuminate\Support\Str;

class BusinessSlugService
{
    public function generate(string $tradeName): string
    {
        $baseSlug = Str::slug($tradeName) ?: 'empresa';
        $baseSlug = substr($baseSlug, 0, 150);
        $slug = $baseSlug;
        $suffix = 2;

        while (Business::query()->where('catalog_slug', $slug)->exists()) {
            $slug = substr($baseSlug, 0, 150 - strlen((string) $suffix)).'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
