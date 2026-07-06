<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\EmailVerificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:6,1')->name('register');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('user', [AuthController::class, 'user'])->name('user');
        Route::post('verify-otp', [EmailVerificationController::class, 'verify'])->middleware('throttle:6,1')->name('verify-otp');
        Route::post('resend-otp', [EmailVerificationController::class, 'resend'])->middleware('throttle:3,1')->name('resend-otp');
    });
});
