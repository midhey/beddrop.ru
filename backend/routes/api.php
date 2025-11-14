<?php
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\MeController;
use App\Http\Controllers\Auth\LogoutController;
use Illuminate\Support\Facades\Route;

Route::get('/ping', fn () => ['message' => 'pong']);

Route::prefix('/v1')->group(function () {


    Route::prefix('/auth')->group(function () {
        Route::post('register', RegisterController::class);
        Route::post('login', LoginController::class);

        Route::middleware('auth:api')->group(function () {
            Route::get('me', MeController::class);
            Route::post('logout', LogoutController::class);
        });
    });
});
