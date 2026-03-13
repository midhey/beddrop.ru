<?php

namespace App\Providers;

use App\Models\Media;
use App\Models\Restaurant;
use App\Policies\MediaPolicy;
use App\Policies\RestaurantPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Media::class => MediaPolicy::class,
        Restaurant::class => RestaurantPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
