<?php

namespace App\Actions\Courier;

use App\Enums\CourierProfileStatus;
use App\Enums\CourierShiftStatus;
use App\Models\CourierProfile;
use App\Models\CourierShift;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;

class StartCourierShift
{
    public function __invoke(User $user): CourierShift
    {
        $profile = $this->resolveActiveCourierProfile($user);

        $openShift = CourierShift::where('courier_user_id', $profile->user_id)
            ->where('status', CourierShiftStatus::OPEN->value)
            ->first();

        if ($openShift) {
            throw new HttpResponseException(response()->json([
                'message' => 'У вас уже есть открытая смена',
                'shift' => $openShift,
            ], 422));
        }

        return CourierShift::create([
            'courier_user_id' => $profile->user_id,
            'started_at' => now(),
            'status' => CourierShiftStatus::OPEN->value,
        ]);
    }

    private function resolveActiveCourierProfile(User $user): CourierProfile
    {
        $profile = $user->courierProfile;

        if (! $profile || $profile->status !== CourierProfileStatus::ACTIVE->value) {
            abort(403, 'Профиль курьера не найден или отключён.');
        }

        return $profile;
    }
}
