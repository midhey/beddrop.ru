<?php

namespace App\Actions\Courier;

use App\Enums\CourierProfileStatus;
use App\Enums\CourierShiftStatus;
use App\Enums\OrderStatus;
use App\Models\CourierProfile;
use App\Models\CourierShift;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;

class EndCourierShift
{
    public function __invoke(User $user): CourierShift
    {
        $profile = $this->resolveActiveCourierProfile($user);

        $shift = CourierShift::where('courier_user_id', $profile->user_id)
            ->where('status', CourierShiftStatus::OPEN->value)
            ->first();

        if (! $shift) {
            throw new HttpResponseException(response()->json([
                'message' => 'Нет открытой смены',
            ], 422));
        }

        $hasActiveOrders = Order::query()
            ->where('courier_id', $profile->user_id)
            ->whereIn('status', [
                OrderStatus::COURIER_ASSIGNED->value,
                OrderStatus::PICKED_UP->value,
            ])
            ->exists();

        if ($hasActiveOrders) {
            throw new HttpResponseException(response()->json([
                'message' => 'Нельзя закрыть смену: у вас есть незавершённые заказы.',
            ], 422));
        }

        $shift->ended_at = now();
        $shift->status = CourierShiftStatus::CLOSED->value;
        $shift->save();

        return $shift;
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
