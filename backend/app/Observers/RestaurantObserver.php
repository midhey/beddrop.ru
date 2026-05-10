<?php

namespace App\Observers;

use App\Models\Restaurant;
use App\Support\PublicDataCache;
use Illuminate\Support\Str;

class RestaurantObserver
{
    public function creating(Restaurant $restaurant): void
    {
        if (empty($restaurant->slug)) {
            $restaurant->slug = static::makeUniqueSlug($restaurant->name);
        }
    }

    public function saved(Restaurant $restaurant): void
    {
        $this->flushPublicData($restaurant);
    }

    public function deleted(Restaurant $restaurant): void
    {
        $this->flushPublicData($restaurant);
    }

    public static function makeUniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'restaurant';
        $slug = $base;
        $counter = 2;

        while (Restaurant::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function flushPublicData(Restaurant $restaurant): void
    {
        PublicDataCache::flushRestaurants();
        PublicDataCache::flushRestaurantDetails();
        PublicDataCache::flushRestaurantMenu($restaurant->id);
    }
}
