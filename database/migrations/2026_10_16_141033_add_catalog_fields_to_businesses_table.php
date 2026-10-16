<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('catalog_slug', 160)->nullable()->unique();
            $table->text('catalog_api_token')->nullable();
            $table->timestamp('catalog_api_token_expires_at')->nullable()->index();
        });

        $usedSlugs = [];

        DB::table('businesses')
            ->select(['id', 'trade_name'])
            ->orderBy('id')
            ->each(function (object $business) use (&$usedSlugs): void {
                $baseSlug = Str::slug((string) $business->trade_name) ?: 'empresa';
                $baseSlug = substr($baseSlug, 0, 150);
                $slug = $baseSlug;
                $suffix = 2;

                while (isset($usedSlugs[$slug])) {
                    $slug = substr($baseSlug, 0, 150 - strlen((string) $suffix)).'-'.$suffix;
                    $suffix++;
                }

                $usedSlugs[$slug] = true;

                DB::table('businesses')
                    ->where('id', $business->id)
                    ->update(['catalog_slug' => $slug]);
            });

        Schema::table('businesses', function (Blueprint $table) {
            $table->string('catalog_slug', 160)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropUnique(['catalog_slug']);
            $table->dropIndex(['catalog_api_token_expires_at']);
            $table->dropColumn([
                'catalog_slug',
                'catalog_api_token',
                'catalog_api_token_expires_at',
            ]);
        });
    }
};
