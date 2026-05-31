<?php

namespace App\Actions\Restaurant;

use App\Actions\Order\TransitionOrderStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MarkRestaurantOrderReady
{
    public function __construct(
        private readonly TransitionOrderStatus $transitionOrderStatus,
    ) {}

    public function __invoke(Order $order, User $user): Order
    {
        if ($order->status !== OrderStatus::ACCEPTED_BY_RESTAURANT->value) {
            abort(422, 'Отметить готовность можно только для принятого рестораном заказа.');
        }

        return DB::transaction(function () use ($order, $user) {
            return ($this->transitionOrderStatus)(
                $order,
                OrderStatus::READY_FOR_PICKUP,
                [
                    'by_user_id' => $user->id,
                ],
                load: ['user', 'items.product', 'events', 'deliveryAddress', 'restaurant.address', 'routeSegments'],
            );
        });
    }
}
