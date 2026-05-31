<?php

namespace App\Actions\Restaurant;

use App\Actions\Order\TransitionOrderStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AcceptRestaurantOrder
{
    public function __construct(
        private readonly TransitionOrderStatus $transitionOrderStatus,
    ) {}

    public function __invoke(Order $order, User $user): Order
    {
        if ($order->status !== OrderStatus::CREATED->value) {
            abort(422, 'Этот заказ уже обработан и не может быть принят.');
        }

        if ($order->payment_status !== PaymentStatus::PAID->value) {
            abort(422, 'Заказ еще не оплачен.');
        }

        return DB::transaction(function () use ($order, $user) {
            return ($this->transitionOrderStatus)(
                $order,
                OrderStatus::ACCEPTED_BY_RESTAURANT,
                [
                    'by_user_id' => $user->id,
                ],
                load: ['user', 'items.product', 'events', 'deliveryAddress', 'restaurant.address', 'routeSegments'],
            );
        });
    }
}
