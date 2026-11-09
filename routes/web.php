<?php

use App\Http\Controllers\Account\AddressController;
use App\Http\Controllers\Account\OrderHistoryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Storefront\AboutController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\ContactController;
use App\Http\Controllers\Storefront\CouponController;
use App\Http\Controllers\Storefront\CustomOrderController;
use App\Http\Controllers\Storefront\NewsletterController;
use App\Http\Controllers\Storefront\PageController;
use App\Http\Controllers\Storefront\PolicyController;
use App\Http\Controllers\Storefront\ReviewController;
use App\Http\Controllers\Storefront\StockAlertController;
use App\Http\Controllers\Storefront\TrackOrderController;
use App\Http\Controllers\Storefront\WishlistController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\ProductController;
use App\Http\Controllers\Storefront\SitemapController;
use App\Http\Controllers\Webhook\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', [AboutController::class, 'index'])->name('about');
Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:5,1')->name('contact.store');

Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');

Route::post('/products/{slug}/reviews', [ReviewController::class, 'store'])->middleware(['module:reviews', 'throttle:5,1'])->name('reviews.store');

Route::get('/wishlist', [WishlistController::class, 'index'])->middleware('module:wishlist')->name('wishlist.index');
Route::post('/wishlist/toggle', [WishlistController::class, 'toggle'])->middleware(['module:wishlist', 'throttle:60,1'])->name('wishlist.toggle');

Route::get('/custom-order', [CustomOrderController::class, 'create'])->middleware('module:custom_orders')->name('custom-order.create');
Route::post('/custom-order', [CustomOrderController::class, 'store'])->middleware(['module:custom_orders', 'throttle:5,1'])->name('custom-order.store');

Route::get('/track-order', [TrackOrderController::class, 'form'])->middleware('module:order_tracking')->name('track-order');
Route::post('/track-order', [TrackOrderController::class, 'lookup'])->middleware(['module:order_tracking', 'throttle:10,1'])->name('track-order.lookup');

Route::get('/policies', [PolicyController::class, 'index'])->name('policies');
Route::get('/p/{slug}', [PageController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('pages.show');
Route::post('/stock-alerts', [StockAlertController::class, 'store'])->middleware(['module:back_in_stock', 'throttle:10,1'])->name('stock-alerts.store');
Route::post('/newsletter', [NewsletterController::class, 'store'])->middleware(['module:newsletter_popup', 'throttle:10,1'])->name('newsletter.store');

// Coupons: switch on/off under Dashboard → Modules (off by default).
Route::post('/cart/coupon', [CouponController::class, 'store'])->middleware(['module:coupons', 'throttle:20,1'])->name('cart.coupon.store');
Route::delete('/cart/coupon', [CouponController::class, 'destroy'])->middleware('module:coupons')->name('cart.coupon.destroy');

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart', [CartController::class, 'store'])->middleware('throttle:60,1')->name('cart.store');
Route::patch('/cart/{cartItem}', [CartController::class, 'update'])->middleware('throttle:60,1')->name('cart.update');
Route::delete('/cart/{cartItem}', [CartController::class, 'destroy'])->middleware('throttle:60,1')->name('cart.destroy');

Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::get('/checkout/shipping-quote', [CheckoutController::class, 'shippingQuote'])->middleware('throttle:30,1')->name('checkout.shipping-quote');
Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('checkout.store');
Route::get('/checkout/success/{order}', [CheckoutController::class, 'success'])->name('checkout.success');
Route::get('/checkout/pay/{order}', [CheckoutController::class, 'pay'])->name('checkout.pay');
Route::get('/checkout/pay/{order}/confirm', [CheckoutController::class, 'confirmPayment'])->name('checkout.pay.confirm');

Route::post('/webhooks/stripe', StripeWebhookController::class)->name('webhooks.stripe');

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/account/addresses', [AddressController::class, 'index'])->name('account.addresses');
    Route::post('/account/addresses', [AddressController::class, 'store'])->name('account.addresses.store');
    Route::delete('/account/addresses/{address}', [AddressController::class, 'destroy'])->name('account.addresses.destroy');

    Route::get('/account/orders', [OrderHistoryController::class, 'index'])->name('account.orders.index');
    Route::get('/account/orders/{order}', [OrderHistoryController::class, 'show'])->name('account.orders.show');
    Route::get('/account/orders/{order}/invoice', [OrderHistoryController::class, 'invoice'])->middleware('module:invoice')->name('account.orders.invoice');
});

require __DIR__.'/auth.php';
