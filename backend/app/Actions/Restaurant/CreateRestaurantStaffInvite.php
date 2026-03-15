<?php

namespace App\Actions\Restaurant;

use App\Enums\RestaurantStaffRole;
use App\Models\Restaurant;
use App\Models\RestaurantStaffInvite;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CreateRestaurantStaffInvite
{
    public function __invoke(Restaurant $restaurant, User $actor, array $data): RestaurantStaffInvite
    {
        $role = RestaurantStaffRole::from($data['role']);
        $ttl = (int) ($data['expires_in_minutes'] ?? 5);

        return RestaurantStaffInvite::create([
            'restaurant_id' => $restaurant->id,
            'invited_by_user_id' => $actor->id,
            'token' => Str::random(64),
            'role' => $role->value,
            'expires_at' => Carbon::now()->addMinutes($ttl),
        ])->load(['restaurant', 'invitedBy']);
    }
}
