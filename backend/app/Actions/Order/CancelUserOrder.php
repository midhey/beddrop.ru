<?php

namespace App\Actions\Order;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CancelUserOrder
{
    public function __construct(
        private readonly TransitionOrderStatus $transitionOrderStatus,
    ) {}

    public function __invoke(Order $order, User $user): Order
    {
        if ($order->user_id !== $user->id) {
            abort(404);
        }

        if (! in_array($order->status, [
            OrderStatus::CREATED->value,
            OrderStatus::ACCEPTED_BY_RESTAURANT->value,
        ], true)) {
            abort(422, 'Вы не можете отменить заказ на данном этапе. Пожалуйста, свяжитесь с поддержкой.');
        }

        return DB::transaction(function () use ($order, $user) {
            return ($this->transitionOrderStatus)(
                $order,
                OrderStatus::CANCELED_BY_USER,
                [
                    'by_user_id' => $user->id,
                    'reason' => 'Отменено пользователем',
                ],
                load: ['restaurant.address', 'items.product', 'events', 'deliveryAddress'],
            );
        });
    }
}
