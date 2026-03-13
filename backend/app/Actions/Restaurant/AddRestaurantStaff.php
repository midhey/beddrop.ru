<?php

namespace App\Actions\Restaurant;

use App\Enums\RestaurantStaffRole;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;

class AddRestaurantStaff
{
    public function __invoke(Restaurant $restaurant, array $data): void
    {
        $role = RestaurantStaffRole::from($data['role']);

        if ($role === RestaurantStaffRole::OWNER) {
            abort(403, 'Назначение владельца через этот endpoint недоступно.');
        }

        $user = User::findOrFail($data['user_id']);

        if ($restaurant->users()->where('users.id', $user->id)->exists()) {
            throw new HttpResponseException(response()->json([
                'message' => 'Пользователь уже привязан к этому ресторану',
            ], 422));
        }

        $restaurant->users()->attach($user->id, [
            'role' => $role->value,
        ]);
    }
}
