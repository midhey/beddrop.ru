<?php

namespace App\Actions\Restaurant;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

class AcceptRestaurantOrder
{
    public function __invoke(Order $order, User $user): Order
    {
        if ($order->status !== OrderStatus::CREATED->value) {
            throw new HttpResponseException(response()->json([
                'message' => 'Этот заказ уже обработан и не может быть принят.',
            ], 422));
        }

        return DB::transaction(function () use ($order, $user) {
            $order->status = OrderStatus::ACCEPTED_BY_RESTAURANT->value;
            $order->save();

            OrderEvent::create([
                'order_id' => $order->id,
                'event' => OrderStatus::ACCEPTED_BY_RESTAURANT->value,
                'payload' => [
                    'by_user_id' => $user->id,
                ],
            ]);

            return $order->load(['user', 'items.product', 'events']);
        });
    }
}
