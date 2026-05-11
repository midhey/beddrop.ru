<?php

namespace App\Actions\Restaurant;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

class MarkRestaurantOrderReady
{
    public function __invoke(Order $order, User $user): Order
    {
        if ($order->status !== OrderStatus::ACCEPTED_BY_RESTAURANT->value) {
            throw new HttpResponseException(response()->json([
                'message' => 'Отметить готовность можно только для принятого рестораном заказа.',
            ], 422));
        }

        return DB::transaction(function () use ($order, $user) {
            $order->status = OrderStatus::READY_FOR_PICKUP->value;
            $order->save();

            OrderEvent::create([
                'order_id' => $order->id,
                'event' => OrderStatus::READY_FOR_PICKUP->value,
                'payload' => [
                    'by_user_id' => $user->id,
                ],
            ]);

            return $order->load(['user', 'items.product', 'events', 'deliveryAddress', 'restaurant.address', 'routeSegments']);
        });
    }
}
