<?php

use App\Http\Controllers\Api\CatalogSaleController;
use App\Http\Middleware\EnsureCatalogBusiness;
use App\Http\Middleware\SetBusinessContext;
use Illuminate\Support\Facades\Route;

Route::post('/catalog/sales', [CatalogSaleController::class, 'store'])
    ->middleware([
        'auth:sanctum',
        SetBusinessContext::class,
        EnsureCatalogBusiness::class,
        'abilities:catalog:sales:create',
        'throttle:catalog-api',
    ])
    ->name('api.catalog.sales.store');
