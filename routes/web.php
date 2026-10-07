<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProfileController;
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
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])
        ->whereNumber('customer')
        ->name('customers.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
