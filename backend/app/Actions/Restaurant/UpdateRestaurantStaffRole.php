<?php

namespace App\Actions\Restaurant;

use App\Enums\RestaurantStaffRole;
use App\Models\Restaurant;
use App\Models\User;

class UpdateRestaurantStaffRole
{
    public function __invoke(Restaurant $restaurant, User $user, array $data): void
    {
        $role = RestaurantStaffRole::from($data['role']);

        $restaurantUser = $restaurant->users()
            ->select('users.id')
            ->withPivot('role')
            ->where('users.id', $user->id)
            ->first();

        if (! $restaurantUser) {
            abort(404, 'Пользователь не является сотрудником этого ресторана');
        }

        $currentRole = $restaurantUser->pivot->role;

        if ($role === RestaurantStaffRole::OWNER && $currentRole !== RestaurantStaffRole::OWNER->value) {
            abort(403, 'Назначение владельца через этот endpoint недоступно.');
        }

        if ($currentRole === RestaurantStaffRole::OWNER->value && $role !== RestaurantStaffRole::OWNER) {
            abort(422, 'Нельзя изменить роль текущего владельца через этот endpoint');
        }

        $restaurant->users()->updateExistingPivot($user->id, [
            'role' => $role->value,
        ]);
    }
}
