<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SellerController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware('auth')->group(function () {
    Route::view('/dashboard', 'dashboard')
        ->middleware('verified')
        ->name('dashboard');

    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])
        ->whereNumber('customer')
        ->name('customers.update');
    Route::patch('/customers/{customer}/deactivate', [CustomerController::class, 'deactivate'])
        ->whereNumber('customer')
        ->name('customers.deactivate');

    Route::get('/sellers', [SellerController::class, 'index'])->name('sellers.index');
    Route::post('/sellers', [SellerController::class, 'store'])->name('sellers.store');
    Route::put('/sellers/{seller}', [SellerController::class, 'update'])
        ->whereNumber('seller')
        ->name('sellers.update');
    Route::patch('/sellers/{seller}/deactivate', [SellerController::class, 'deactivate'])
        ->whereNumber('seller')
        ->name('sellers.deactivate');

    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::put('/products/{product}', [ProductController::class, 'update'])
        ->whereNumber('product')
        ->name('products.update');
    Route::patch('/products/{product}/deactivate', [ProductController::class, 'deactivate'])
        ->whereNumber('product')
        ->name('products.deactivate');

    Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
    Route::post('/sales', [SaleController::class, 'store'])->name('sales.store');
    Route::get('/sales/{sale}', [SaleController::class, 'show'])
        ->whereNumber('sale')
        ->name('sales.show');
    Route::put('/sales/{sale}', [SaleController::class, 'update'])
        ->whereNumber('sale')
        ->name('sales.update');
    Route::patch('/sales/{sale}/deactivate', [SaleController::class, 'deactivate'])
        ->whereNumber('sale')
        ->name('sales.deactivate');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
