<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    require __DIR__.'/api/v1/catalog.php';
    require __DIR__.'/api/v1/auth.php';
    require __DIR__.'/api/v1/cart.php';
    require __DIR__.'/api/v1/checkout.php';

    Route::middleware(['auth:sanctum', 'verified.api'])->group(function () {
        require __DIR__.'/api/v1/account.php';
    });

    Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->name('admin.')->group(function () {
        require __DIR__.'/api/v1/admin.php';
    });
});
