<?php

namespace App\Providers;

use App\Models\Restaurant;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use App\Observers\OrderObserver;
use App\Observers\ProductCategoryObserver;
use App\Observers\ProductImageObserver;
use App\Observers\ProductObserver;
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
        Order::observe(OrderObserver::class);
        Product::observe(ProductObserver::class);
        ProductCategory::observe(ProductCategoryObserver::class);
        ProductImage::observe(ProductImageObserver::class);
    }
}
