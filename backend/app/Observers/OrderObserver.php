<?php

namespace App\Observers;

use App\Enums\PaymentStatus;
use App\Models\Order;

class OrderObserver
{
    public function updating(Order $order): void
    {
        if (
            ! $order->isDirty('payment_status') ||
            $order->payment_status !== PaymentStatus::PAID->value
        ) {
            return;
        }

        $time = $order->logistics_snapshot_json['time'] ?? null;

        if (! is_array($time)) {
            return;
        }

        $prep = $this->minutes($time['prep'] ?? null);
        $pickupBuffer = $this->minutes($time['pickup_buffer'] ?? null);
        $total = $this->minutes($time['total'] ?? null);

        if ($total === null) {
            return;
        }

        $paidAt = now();
        $order->estimated_pickup_at = $paidAt->copy()->addMinutes(($prep ?? 0) + ($pickupBuffer ?? 0));
        $order->estimated_delivery_at = $paidAt->copy()->addMinutes($total);
    }

    private function minutes(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        return max(0, (int) ceil((float) $value));
    }
}
