<?php

use App\Http\Controllers\Api\V1\Checkout\CheckoutController;
use Illuminate\Support\Facades\Route;

Route::prefix('checkout')->name('checkout.')->group(function () {
    Route::get('shipping-quote', [CheckoutController::class, 'shippingQuote'])->middleware('throttle:checkout-api')->name('shipping-quote');
    Route::post('/', [CheckoutController::class, 'store'])->middleware('throttle:checkout-api')->name('store');
    Route::post('orders/{order}/confirm-payment', [CheckoutController::class, 'confirmPayment'])
        ->middleware('throttle:checkout-api')
        ->name('confirm-payment');
});
