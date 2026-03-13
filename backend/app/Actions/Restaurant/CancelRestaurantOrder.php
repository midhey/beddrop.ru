<?php

namespace App\Actions\Restaurant;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

class CancelRestaurantOrder
{
    public function __invoke(Order $order, User $user, array $data): Order
    {
        if (in_array($order->status, [
            OrderStatus::DELIVERED->value,
            OrderStatus::CANCELED_BY_USER->value,
            OrderStatus::CANCELED_BY_RESTAURANT->value,
        ], true)) {
            throw new HttpResponseException(response()->json([
                'message' => 'Этот заказ уже завершён и не может быть отменён рестораном.',
            ], 422));
        }

        return DB::transaction(function () use ($order, $user, $data) {
            $order->status = OrderStatus::CANCELED_BY_RESTAURANT->value;
            $order->save();

            OrderEvent::create([
                'order_id' => $order->id,
                'event' => OrderStatus::CANCELED_BY_RESTAURANT->value,
                'payload' => [
                    'by_user_id' => $user->id,
                    'reason' => $data['reason'] ?? null,
                ],
            ]);

            return $order->load(['user', 'items.product', 'events']);
        });
    }
}
