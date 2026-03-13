<?php

namespace App\Policies;

use App\Enums\RestaurantStaffRole;
use App\Models\Restaurant;
use App\Models\User;

class RestaurantPolicy
{
    public function view(?User $user, Restaurant $restaurant): bool
    {
        if($restaurant->is_active) {
            return true;
        }

        if(!$user) {
            return false;
        }

        return $user->is_admin
            || $user->hasRestaurantRole($restaurant, [
                RestaurantStaffRole::OWNER,
                RestaurantStaffRole::MANAGER,
                RestaurantStaffRole::STAFF,
            ]);
    }

    public function create(User $user): bool
    {
        return !$user->is_banned;
    }

    public function update(User $user, Restaurant $restaurant): bool
    {
        return $user->is_admin
            || $user->hasRestaurantRole($restaurant, [
                RestaurantStaffRole::OWNER,
                RestaurantStaffRole::MANAGER,
            ]);
    }

    public function delete(User $user, Restaurant $restaurant): bool
    {
        return $user->is_admin
            || $user->hasRestaurantRole($restaurant, [RestaurantStaffRole::OWNER]);
    }

    public function manageStaff(User $user, Restaurant $restaurant): bool
    {
        return $user->is_admin
            || $user->hasRestaurantRole($restaurant, [
                RestaurantStaffRole::OWNER,
                RestaurantStaffRole::MANAGER,
            ]);
    }

    public function manageOrders(User $user, Restaurant $restaurant): bool
    {
        return $user->is_admin
            || $user->isStaffOf($restaurant);
    }
}
