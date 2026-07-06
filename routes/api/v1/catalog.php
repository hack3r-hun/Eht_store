<?php

use App\Http\Controllers\Api\V1\Catalog\CategoryController;
use App\Http\Controllers\Api\V1\Catalog\ContactController;
use App\Http\Controllers\Api\V1\Catalog\PageController;
use App\Http\Controllers\Api\V1\Catalog\ProductController;
use App\Http\Controllers\Api\V1\Catalog\ShopController;
use Illuminate\Support\Facades\Route;

Route::get('products', [ProductController::class, 'index'])->name('products.index');
Route::get('products/{slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('pages/{slug}', [PageController::class, 'show'])->name('pages.show');
Route::get('shop', [ShopController::class, 'show'])->name('shop.show');
Route::post('contact', [ContactController::class, 'store'])->middleware('throttle:5,1')->name('contact.store');
