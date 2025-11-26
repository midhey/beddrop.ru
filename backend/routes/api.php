<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RefreshController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductImageController;
use App\Http\Controllers\Profile\PasswordController;
use App\Http\Controllers\Profile\ProfileController;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\RestaurantStaffController;
use Illuminate\Support\Facades\Route;

Route::get('/ping', fn() => ['message' => 'pong']);

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
    });

    Route::prefix('/restaurants')->group(function () {
        Route::get('/', [RestaurantController::class, 'index']);

        Route::prefix('/{restaurant:slug}')->group(function () {
            Route::get('/', [RestaurantController::class, 'show']);

            // Руты работы с персоналом
            Route::middleware('auth:api')->prefix('/users')->group(function () {
                Route::get('/', [RestaurantStaffController::class, 'index']);
                Route::post('/', [RestaurantStaffController::class, 'store']);
                Route::put('/{user}', [RestaurantStaffController::class, 'update']);
                Route::delete('/{user}', [RestaurantStaffController::class, 'destroy']);
            });

            // Руты работы с продуктами
            Route::prefix('/products')->group(function () {
                Route::get('/', [ProductController::class, 'index']);
                Route::get('/{product}', [ProductController::class, 'show']);

                Route::middleware('auth:api')->group(function () {
                    Route::post('/', [ProductController::class, 'store']);
                    Route::put('/{product}', [ProductController::class, 'update']);
                    Route::delete('/{product}', [ProductController::class, 'destroy']);

                    Route::post('/{product}/images', [ProductImageController::class, 'store']);
                    Route::put('/{product}/images/{image}', [ProductImageController::class, 'update']);
                    Route::delete('/{product}/images/{image}', [ProductImageController::class, 'destroy']);
                });
            });
        });

        Route::middleware('auth:api')->group(function () {
            Route::post('/', [RestaurantController::class, 'store']);
            Route::put('/{restaurant}', [RestaurantController::class, 'update']);
            Route::delete('/{restaurant}', [RestaurantController::class, 'destroy']);
        });
    });

    Route::prefix('/product-categories')->group(function () {
        Route::get('/', [ProductCategoryController::class, 'index']);

        Route::middleware('auth:api')->group(function () {
            Route::post('/', [ProductCategoryController::class, 'store']);
            Route::put('/{category}', [ProductCategoryController::class, 'update']);
            Route::delete('/{category}', [ProductCategoryController::class, 'destroy']);
        });
    });

    Route::middleware('auth:api')->prefix('/media')->group(function () {
        Route::post('/', [MediaController::class, 'store']);
        Route::delete('/{media}', [MediaController::class, 'destroy']);
    });
});
