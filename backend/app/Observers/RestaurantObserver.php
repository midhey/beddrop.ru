<?php

namespace App\Observers;

use App\Models\Restaurant;
use Illuminate\Support\Str;

class RestaurantObserver
{
    public function creating(Restaurant $restaurant)
    {
        if (empty($restaurant->slug)) {
            $restaurant->slug = static::makeUniqueSlug($restaurant->name);
        }
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
}
