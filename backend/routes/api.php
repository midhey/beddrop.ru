<?php

use Illuminate\Support\Facades\Route;

Route::get('/ping', fn () => ['message' => 'pong']);

Route::prefix('/v1')->group(function () {
    // Route::get('/restaurants', [RestaurantController::class, 'index']);
});
