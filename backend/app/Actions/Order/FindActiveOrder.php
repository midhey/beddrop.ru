<?php

namespace App\Actions\Order;

use App\Enums\OrderStatus;
use App\Models\Order;

class FindActiveOrder
{
    public function __invoke(int $userId): ?Order
    {
        return Order::query()
            ->where('user_id', $userId)
            ->whereNotIn('status', [
                OrderStatus::DELIVERED->value,
                OrderStatus::CANCELED_BY_USER->value,
                OrderStatus::CANCELED_BY_RESTAURANT->value,
            ])
            ->with(['restaurant:id,name,slug'])
            ->withSum('items as items_count', 'quantity')
            ->orderByDesc('created_at')
            ->first();
    }
}
