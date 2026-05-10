<?php

namespace App\Observers;

use App\Models\ProductCategory;
use App\Support\PublicDataCache;

class ProductCategoryObserver
{
    public function saved(ProductCategory $category): void
    {
        $this->flushPublicData();
    }

    public function deleted(ProductCategory $category): void
    {
        $this->flushPublicData();
    }

    private function flushPublicData(): void
    {
        PublicDataCache::flushCategories();
        PublicDataCache::flushRestaurants();
        PublicDataCache::flushMenus();
    }
}
