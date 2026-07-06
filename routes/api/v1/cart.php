<?php

use App\Http\Controllers\Api\V1\Cart\CartController;
use Illuminate\Support\Facades\Route;

Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'show'])->name('show');
    Route::post('items', [CartController::class, 'store'])->name('items.store');
    Route::patch('items/{cartItem}', [CartController::class, 'update'])->name('items.update');
    Route::delete('items/{cartItem}', [CartController::class, 'destroy'])->name('items.destroy');
});
