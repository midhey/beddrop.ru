<?php

namespace App\Actions\Restaurant;

use App\Enums\RestaurantStaffRole;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;

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
            throw new HttpResponseException(response()->json([
                'message' => 'Пользователь не является сотрудником этого ресторана',
            ], 404));
        }

        $currentRole = $restaurantUser->pivot->role;

        if ($role === RestaurantStaffRole::OWNER && $currentRole !== RestaurantStaffRole::OWNER->value) {
            abort(403, 'Назначение владельца через этот endpoint недоступно.');
        }

        if ($currentRole === RestaurantStaffRole::OWNER->value && $role !== RestaurantStaffRole::OWNER) {
            throw new HttpResponseException(response()->json([
                'message' => 'Нельзя изменить роль текущего владельца через этот endpoint',
            ], 422));
        }

        $restaurant->users()->updateExistingPivot($user->id, [
            'role' => $role->value,
        ]);
    }
}
