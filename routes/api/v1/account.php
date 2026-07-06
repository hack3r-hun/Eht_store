<?php

use App\Http\Controllers\Api\V1\Account\AddressController;
use App\Http\Controllers\Api\V1\Account\OrderController;
use App\Http\Controllers\Api\V1\Account\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
Route::delete('profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

Route::get('addresses', [AddressController::class, 'index'])->name('addresses.index');
Route::post('addresses', [AddressController::class, 'store'])->name('addresses.store');
Route::delete('addresses/{address}', [AddressController::class, 'destroy'])->name('addresses.destroy');

Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
Route::get('orders/{order}/invoice', [OrderController::class, 'invoice'])->name('orders.invoice');
