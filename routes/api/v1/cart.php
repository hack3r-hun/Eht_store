<?php

use App\Http\Controllers\Api\V1\Cart\CartController;
use Illuminate\Support\Facades\Route;

Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'show'])->name('show');
    Route::post('items', [CartController::class, 'store'])->middleware('throttle:60,1')->name('items.store');
    Route::patch('items/{cartItem}', [CartController::class, 'update'])->middleware('throttle:60,1')->name('items.update');
    Route::delete('items/{cartItem}', [CartController::class, 'destroy'])->middleware('throttle:60,1')->name('items.destroy');
});
