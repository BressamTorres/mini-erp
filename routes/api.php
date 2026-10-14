<?php

use App\Http\Controllers\Api\CatalogSaleController;
use Illuminate\Support\Facades\Route;

Route::post('/catalog/sales', [CatalogSaleController::class, 'store'])
    ->middleware(['auth:sanctum', 'abilities:catalog:sales:create'])
    ->name('api.catalog.sales.store');
