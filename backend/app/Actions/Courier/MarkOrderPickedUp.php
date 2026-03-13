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

class MarkOrderPickedUp
{
    public function __invoke(User $user, Order $order): Order
    {
        $profile = $this->ensureOpenShift($user);

        if ($order->courier_id !== $profile->user_id) {
            abort(403, 'Вы не назначены на этот заказ.');
        }

        if (! in_array($order->status, [OrderStatus::COURIER_ASSIGNED->value], true)) {
            throw new HttpResponseException(response()->json([
                'message' => 'Нельзя перевести заказ в статус PICKED_UP',
            ], 422));
        }

        return DB::transaction(function () use ($order, $profile) {
            $order->status = OrderStatus::PICKED_UP->value;
            $order->save();

            OrderEvent::create([
                'order_id' => $order->id,
                'event' => OrderStatus::PICKED_UP->value,
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
