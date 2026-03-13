<?php

namespace App\Actions\Courier;

use App\Enums\CourierProfileStatus;
use App\Enums\CourierShiftStatus;
use App\Enums\OrderStatus;
use App\Models\CourierProfile;
use App\Models\CourierShift;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

class AssignCourierOrder
{
    public function __invoke(User $user, Order $order): Order
    {
        $profile = $this->ensureOpenShift($user);

        if (
            $order->status !== OrderStatus::ACCEPTED_BY_RESTAURANT->value ||
            $order->courier_id !== null
        ) {
            throw new HttpResponseException(response()->json([
                'message' => 'Этот заказ нельзя взять в работу',
            ], 422));
        }

        return DB::transaction(function () use ($order, $profile) {
            $order->courier_id = $profile->user_id;
            $order->status = OrderStatus::COURIER_ASSIGNED->value;
            $order->save();

            OrderEvent::create([
                'order_id' => $order->id,
                'event' => OrderStatus::COURIER_ASSIGNED->value,
                'payload' => [
                    'courier_user_id' => $profile->user_id,
                ],
            ]);

            return $order->load([
                'restaurant.address',
                'items.product',
                'deliveryAddress',
            ]);
        });
    }

    private function ensureOpenShift(User $user): CourierProfile
    {
        $profile = $user->courierProfile;

        if (! $profile || $profile->status !== CourierProfileStatus::ACTIVE->value) {
            abort(403, 'Профиль курьера не найден или отключён.');
        }

        $hasOpenShift = CourierShift::query()
            ->where('courier_user_id', $profile->user_id)
            ->where('status', CourierShiftStatus::OPEN->value)
            ->exists();

        if (! $hasOpenShift) {
            abort(422, 'У вас нет открытой смены.');
        }

        return $profile;
    }
}
