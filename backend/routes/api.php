<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RefreshController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\Profile\PasswordController;
use App\Http\Controllers\Profile\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/ping', fn () => ['message' => 'pong']);

Route::prefix('/v1')->group(function () {

    Route::prefix('/auth')->group(function () {
        Route::post('register', RegisterController::class);
        Route::post('login', LoginController::class);
        Route::post('refresh', RefreshController::class);

        Route::middleware('auth:api')->group(function () {
            Route::post('logout', LogoutController::class);
        });
    });

    Route::middleware('auth:api')->prefix('/profile')->group(function () {
        Route::get('me', [ProfileController::class, 'show']);
        Route::put('me', [ProfileController::class, 'update']);
        Route::put('password', PasswordController::class);
    } );

    Route::middleware('auth:api')->group(function () {
        Route::post('/media', [MediaController::class, 'store']);
        Route::delete('/media/{media}', [MediaController::class, 'destroy']);
    });

});
