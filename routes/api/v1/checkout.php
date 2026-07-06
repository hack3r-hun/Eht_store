<?php

use App\Http\Controllers\Api\V1\Checkout\CheckoutController;
use Illuminate\Support\Facades\Route;

Route::prefix('checkout')->name('checkout.')->group(function () {
    Route::get('shipping-quote', [CheckoutController::class, 'shippingQuote'])->middleware('throttle:30,1')->name('shipping-quote');
    Route::post('/', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('store');
    Route::post('orders/{order}/confirm-payment', [CheckoutController::class, 'confirmPayment'])->name('confirm-payment');
});
