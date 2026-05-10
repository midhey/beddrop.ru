<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class PublicDataCache
{
    public const CATEGORIES = 'categories';
    public const RESTAURANTS = 'restaurants';
    public const RESTAURANT_DETAILS = 'restaurant-details';
    public const MENUS = 'menus';

    private const VALUE_PREFIX = 'public-data';
    private const VERSION_PREFIX = 'public-data-version';

    public static function remember(string $group, array $parts, Closure $callback): mixed
    {
        return Cache::remember(
            self::key($group, $parts),
            now()->addMinutes(10),
            $callback,
        );
    }

    public static function flushCategories(): void
    {
        self::flush(self::CATEGORIES);
    }

    public static function flushRestaurants(): void
    {
        self::flush(self::RESTAURANTS);
    }

    public static function flushRestaurantDetails(): void
    {
        self::flush(self::RESTAURANT_DETAILS);
    }

    public static function flushMenus(): void
    {
        self::flush(self::MENUS);
    }

    public static function flushRestaurantMenu(int $restaurantId): void
    {
        self::flush(self::restaurantMenuGroup($restaurantId));
    }

    public static function restaurantMenuGroup(int $restaurantId): string
    {
        return self::MENUS . '.' . self::version(self::MENUS) . '.restaurant.' . $restaurantId;
    }

    private static function flush(string $group): void
    {
        Cache::forever(self::versionKey($group), Str::uuid()->toString());
    }

    private static function key(string $group, array $parts): string
    {
        $encodedParts = json_encode($parts, JSON_THROW_ON_ERROR);

        return implode(':', [
            self::VALUE_PREFIX,
            $group,
            self::version($group),
            sha1($encodedParts),
        ]);
    }

    private static function version(string $group): string
    {
        return Cache::rememberForever(
            self::versionKey($group),
            fn () => Str::uuid()->toString(),
        );
    }

    private static function versionKey(string $group): string
    {
        return self::VERSION_PREFIX . ':' . $group;
    }
}
