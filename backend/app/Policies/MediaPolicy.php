<?php

namespace App\Policies;

use App\Enums\RestaurantStaffRole;
use App\Models\Media;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\User;

class MediaPolicy
{
    public function delete(User $user, Media $media): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ((int) $media->uploaded_by_user_id !== (int) $user->id) {
            return false;
        }

        $isUsedByUnmanagedRestaurant = Restaurant::query()
            ->where('logo_media_id', $media->id)
            ->whereDoesntHave('users', function ($query) use ($user) {
                $query
                    ->where('users.id', $user->id)
                    ->whereIn('role', [
                        RestaurantStaffRole::OWNER->value,
                        RestaurantStaffRole::MANAGER->value,
                    ]);
            })
            ->exists();

        if ($isUsedByUnmanagedRestaurant) {
            return false;
        }

        return ! Product::query()
            ->whereHas('images', fn ($query) => $query->where('media_id', $media->id))
            ->whereDoesntHave('restaurant.users', function ($query) use ($user) {
                $query
                    ->where('users.id', $user->id)
                    ->whereIn('role', [
                        RestaurantStaffRole::OWNER->value,
                        RestaurantStaffRole::MANAGER->value,
                    ]);
            })
            ->exists();
    }
}
