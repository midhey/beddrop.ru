<?php

namespace App\Actions\Restaurant;

use App\Actions\Order\TransitionOrderStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CancelRestaurantOrder
{
    public function __construct(
        private readonly TransitionOrderStatus $transitionOrderStatus,
    ) {}

    public function __invoke(Order $order, User $user, array $data): Order
    {
        if (in_array($order->status, [
            OrderStatus::DELIVERED->value,
            OrderStatus::CANCELED_BY_USER->value,
            OrderStatus::CANCELED_BY_RESTAURANT->value,
        ], true)) {
            abort(422, 'Этот заказ уже завершён и не может быть отменён рестораном.');
        }

        return DB::transaction(function () use ($order, $user, $data) {
            return ($this->transitionOrderStatus)(
                $order,
                OrderStatus::CANCELED_BY_RESTAURANT,
                [
                    'by_user_id' => $user->id,
                    'reason' => $data['reason'] ?? null,
                ],
                load: ['user', 'items.product', 'events'],
            );
        });
    }
}
