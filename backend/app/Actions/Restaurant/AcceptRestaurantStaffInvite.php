<?php

namespace App\Actions\Restaurant;

use App\Models\RestaurantStaffInvite;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Carbon;

class AcceptRestaurantStaffInvite
{
    public function __invoke(RestaurantStaffInvite $invite, User $user): void
    {
        if ($invite->accepted_at !== null) {
            throw new HttpResponseException(response()->json([
                'message' => 'Это приглашение уже использовано',
            ], 410));
        }

        if ($invite->expires_at->isPast()) {
            throw new HttpResponseException(response()->json([
                'message' => 'Срок действия приглашения истёк',
            ], 410));
        }

        $restaurant = $invite->restaurant;

        if ($restaurant->users()->where('users.id', $user->id)->exists()) {
            throw new HttpResponseException(response()->json([
                'message' => 'Вы уже подключены к этому ресторану',
            ], 422));
        }

        $restaurant->users()->attach($user->id, [
            'role' => $invite->role,
        ]);

        $invite->forceFill([
            'accepted_by_user_id' => $user->id,
            'accepted_at' => Carbon::now(),
        ])->save();
    }
}
