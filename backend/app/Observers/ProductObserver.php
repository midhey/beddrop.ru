<?php

namespace App\Observers;

use App\Models\Product;
use App\Support\PublicDataCache;

class ProductObserver
{
    public function saved(Product $product): void
    {
        $this->flushPublicData($product);
    }

    public function deleted(Product $product): void
    {
        $this->flushPublicData($product);
    }

    private function flushPublicData(Product $product): void
    {
        PublicDataCache::flushRestaurants();
        PublicDataCache::flushRestaurantMenu($product->restaurant_id);
    }
}
