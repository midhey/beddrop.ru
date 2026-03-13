<?php

namespace App\Providers;

use App\Models\Restaurant;
use App\Observers\RestaurantObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        Restaurant::observe(RestaurantObserver::class);
    }
}
