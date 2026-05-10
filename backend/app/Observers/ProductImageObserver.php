<?php

namespace App\Observers;

use App\Models\ProductImage;
use App\Support\PublicDataCache;

class ProductImageObserver
{
    public function saved(ProductImage $image): void
    {
        $this->flushProductMenu($image);
    }

    public function deleted(ProductImage $image): void
    {
        $this->flushProductMenu($image);
    }

    private function flushProductMenu(ProductImage $image): void
    {
        $restaurantId = $image->product?->restaurant_id
            ?? $image->product()->value('restaurant_id');

        if ($restaurantId !== null) {
            PublicDataCache::flushRestaurantMenu($restaurantId);
        }
    }
}
