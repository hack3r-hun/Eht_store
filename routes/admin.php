<?php

use App\Http\Controllers\Admin\AdminPasswordController;
use App\Http\Controllers\Admin\BuyerController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\ModuleController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StatisticsController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\CustomOrderController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\SiteController;
use App\Http\Controllers\Admin\SubscriberController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('statistics', [StatisticsController::class, 'index'])->name('statistics.index');
    Route::get('statistics/export', [StatisticsController::class, 'export'])->middleware('throttle:10,1')->name('statistics.export');

    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::delete('products/bulk-destroy', [ProductController::class, 'bulkDestroy'])->name('products.bulk-destroy');
    Route::get('products/archived', [ProductController::class, 'archived'])->name('products.archived');
    Route::post('products/{product}/restore', [ProductController::class, 'restore'])->name('products.restore');
    Route::resource('products', ProductController::class)->except(['show']);
    Route::delete('products/{product}/images/{image}', [ProductController::class, 'destroyImage'])->name('products.images.destroy');
    Route::resource('orders', OrderController::class)->only(['index', 'show', 'destroy']);
    Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
    Route::patch('orders/{order}/shipping', [OrderController::class, 'updateShipping'])->name('orders.shipping');
    Route::get('orders/{order}/invoice', [OrderController::class, 'invoice'])->name('orders.invoice');

    Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('customers/everyone', [BuyerController::class, 'index'])->name('customers.buyers');
    Route::get('customers/everyone/export', [BuyerController::class, 'export'])->middleware('throttle:10,1')->name('customers.buyers.export');

    Route::get('messages', [ContactMessageController::class, 'index'])->name('messages.index');
    Route::get('messages/{message}', [ContactMessageController::class, 'show'])->name('messages.show');
    Route::post('messages/{message}/reply', [ContactMessageController::class, 'reply'])->name('messages.reply');
    Route::delete('messages/{message}', [ContactMessageController::class, 'destroy'])->name('messages.destroy');

    Route::get('reviews', [ReviewController::class, 'index'])->name('reviews.index');
    Route::patch('reviews/{review}', [ReviewController::class, 'update'])->name('reviews.update');
    Route::delete('reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

    Route::get('custom-orders', [CustomOrderController::class, 'index'])->name('custom-orders.index');
    Route::patch('custom-orders/{customOrder}', [CustomOrderController::class, 'update'])->name('custom-orders.update');
    Route::delete('custom-orders/{customOrder}', [CustomOrderController::class, 'destroy'])->name('custom-orders.destroy');

    // Coupon pages exist while Dashboard → Modules → Coupon codes is switched on.
    Route::middleware('module:coupons')->group(function () {
        Route::get('coupons', [CouponController::class, 'index'])->name('coupons.index');
        Route::post('coupons', [CouponController::class, 'store'])->name('coupons.store');
        Route::patch('coupons/{coupon}', [CouponController::class, 'update'])->name('coupons.update');
        Route::delete('coupons/{coupon}', [CouponController::class, 'destroy'])->name('coupons.destroy');
    });

    Route::get('site', [SiteController::class, 'edit'])->name('site.edit');
    Route::put('site', [SiteController::class, 'update'])->name('site.update');

    Route::get('subscribers', [SubscriberController::class, 'index'])->name('subscribers.index');

    Route::get('pages', [PageController::class, 'index'])->name('pages.index');
    Route::get('pages/create', [PageController::class, 'create'])->name('pages.create');
    Route::post('pages', [PageController::class, 'store'])->name('pages.store');
    Route::post('pages/upload-image', [PageController::class, 'uploadImage'])->name('pages.upload-image');
    Route::get('pages/{page}/edit', [PageController::class, 'edit'])->name('pages.edit');
    Route::put('pages/{page}', [PageController::class, 'update'])->name('pages.update');
    Route::post('pages/{page}/reset', [PageController::class, 'reset'])->name('pages.reset');
    Route::delete('pages/{page}', [PageController::class, 'destroy'])->name('pages.destroy');

    Route::get('invoice', [InvoiceController::class, 'edit'])->name('invoice.edit');
    Route::put('invoice', [InvoiceController::class, 'update'])->name('invoice.update');
    Route::post('invoice/reset', [InvoiceController::class, 'reset'])->name('invoice.reset');
    Route::get('invoice/preview', [InvoiceController::class, 'preview'])->middleware('throttle:20,1')->name('invoice.preview');

    Route::get('modules', [ModuleController::class, 'index'])->name('modules.index');
    Route::put('modules', [ModuleController::class, 'update'])->name('modules.update');
    Route::get('modules/{module}/edit', [ModuleController::class, 'edit'])->name('modules.edit');
    Route::put('modules/{module}', [ModuleController::class, 'updateModule'])->name('modules.update-module');

    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
    Route::put('settings/password', [AdminPasswordController::class, 'update'])->middleware('throttle:6,1')->name('settings.password');
});
