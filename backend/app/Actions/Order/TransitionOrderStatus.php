<?php

namespace App\Actions\Order;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderEvent;

class TransitionOrderStatus
{
    /**
     * @param  array<string, mixed>  $eventPayload
     * @param  array<string, mixed>  $attributes
     * @param  array<int, string>  $load
     */
    public function __invoke(
        Order $order,
        OrderStatus $status,
        array $eventPayload = [],
        array $attributes = [],
        array $load = [],
    ): Order {
        $order->forceFill(array_merge($attributes, [
            'status' => $status->value,
        ]));
        $order->save();

        OrderEvent::create([
            'order_id' => $order->id,
            'event' => $status->value,
            'payload' => $eventPayload,
        ]);

        return $load === [] ? $order : $order->load($load);
    }
}
