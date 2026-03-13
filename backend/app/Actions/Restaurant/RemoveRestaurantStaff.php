<?php

namespace App\Actions\Restaurant;

use App\Enums\RestaurantStaffRole;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;

class RemoveRestaurantStaff
{
    public function __invoke(User $actor, Restaurant $restaurant, User $user): void
    {
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

        if ($restaurantUser->pivot->role === RestaurantStaffRole::OWNER->value) {
            throw new HttpResponseException(response()->json([
                'message' => 'Нельзя удалить текущего владельца через этот endpoint',
            ], 422));
        }

        if ($actor->id === $user->id) {
            throw new HttpResponseException(response()->json([
                'message' => 'Нельзя удалить самого себя из ресторана',
            ], 422));
        }

        $restaurant->users()->detach($user->id);
    }
}
