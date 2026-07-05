<?php

use App\Http\Controllers\Account\AddressController;
use App\Http\Controllers\Account\OrderHistoryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Storefront\AboutController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\ContactController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\ProductController;
use App\Http\Controllers\Webhook\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', [AboutController::class, 'index'])->name('about');
Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:5,1')->name('contact.store');

Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
Route::patch('/cart/{cartItem}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{cartItem}', [CartController::class, 'destroy'])->name('cart.destroy');

Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('checkout.store');
Route::get('/checkout/success/{order}', [CheckoutController::class, 'success'])->name('checkout.success');
Route::get('/checkout/pay/{order}', [CheckoutController::class, 'pay'])->name('checkout.pay');
Route::get('/checkout/pay/{order}/confirm', [CheckoutController::class, 'confirmPayment'])->name('checkout.pay.confirm');

Route::post('/webhooks/stripe', StripeWebhookController::class)->name('webhooks.stripe');

Route::get('/sitemap.xml', function () {
    $products = \App\Models\Product::where('is_active', true)->get(['slug', 'updated_at']);
    $content = view('sitemap', compact('products'))->render();

    return response($content, 200)->header('Content-Type', 'application/xml');
})->name('sitemap');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/account/addresses', [AddressController::class, 'index'])->name('account.addresses');
    Route::post('/account/addresses', [AddressController::class, 'store'])->name('account.addresses.store');
    Route::delete('/account/addresses/{address}', [AddressController::class, 'destroy'])->name('account.addresses.destroy');

    Route::get('/account/orders', [OrderHistoryController::class, 'index'])->name('account.orders.index');
    Route::get('/account/orders/{order}', [OrderHistoryController::class, 'show'])->name('account.orders.show');
    Route::get('/account/orders/{order}/invoice', [OrderHistoryController::class, 'invoice'])->name('account.orders.invoice');
});

require __DIR__.'/auth.php';
