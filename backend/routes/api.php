<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RefreshController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\Profile\PasswordController;
use App\Http\Controllers\Profile\ProfileController;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\RestaurantStaffController;
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

    Route::prefix('/restaurants')->group(function () {
        Route::get('/', [RestaurantController::class, 'index']);
        Route::get('/{restaurant:slug}', [RestaurantController::class, 'show']);

        Route::middleware('auth:api')->group(function () {
            Route::post('/', [RestaurantController::class, 'store']);
            Route::put('/{restaurant}', [RestaurantController::class, 'update']);
            Route::delete('/{restaurant}', [RestaurantController::class, 'destroy']);

            Route::get('/{restaurant}/users', [RestaurantStaffController::class, 'index']);
            Route::post('/{restaurant}/users', [RestaurantStaffController::class, 'store']);
            Route::put('/{restaurant}/users/{user}', [RestaurantStaffController::class, 'update']);
            Route::delete('/{restaurant}/users/{user}', [RestaurantStaffController::class, 'destroy']);
        });
    });

    Route::middleware('auth:api')->group(function () {
        Route::post('/media', [MediaController::class, 'store']);
        Route::delete('/media/{media}', [MediaController::class, 'destroy']);
    });

});
