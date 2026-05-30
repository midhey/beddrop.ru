<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderEvent;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('orders:cancel-stale-restaurant-acceptance', function () {
    $deadline = now()->subHours(2);
    $canceled = 0;

    Order::query()
        ->where('status', OrderStatus::CREATED->value)
        ->where('payment_status', PaymentStatus::PAID->value)
        ->where('updated_at', '<=', $deadline)
        ->orderBy('id')
        ->chunkById(100, function ($orders) use (&$canceled) {
            foreach ($orders as $staleOrder) {
                $wasCanceled = DB::transaction(function () use ($staleOrder) {
                    $order = Order::query()
                        ->whereKey($staleOrder->id)
                        ->lockForUpdate()
                        ->first();

                    if (
                        ! $order ||
                        $order->status !== OrderStatus::CREATED->value ||
                        $order->payment_status !== PaymentStatus::PAID->value
                    ) {
                        return false;
                    }

                    $order->status = OrderStatus::CANCELED_BY_RESTAURANT->value;
                    $order->save();

                    OrderEvent::create([
                        'order_id' => $order->id,
                        'event' => OrderStatus::CANCELED_BY_RESTAURANT->value,
                        'payload' => [
                            'reason' => 'Автоматическая отмена: ресторан не принял заказ',
                        ],
                    ]);

                    return true;
                });

                if ($wasCanceled) {
                    $canceled++;
                }
            }
        });

    $this->info("Canceled {$canceled} stale paid orders.");
})->purpose('Cancel paid orders that were not accepted by restaurants within 2 hours');

Schedule::command('orders:cancel-stale-restaurant-acceptance')
    ->everyFiveMinutes()
    ->withoutOverlapping();

Artisan::command('orders:expire-stale-pending-payments', function () {
    $ttlMinutes = max(1, (int) config('orders.pending_payment_ttl_minutes', 30));
    $deadline = now()->subMinutes($ttlMinutes);
    $expired = 0;

    Order::query()
        ->where('status', OrderStatus::CREATED->value)
        ->where('payment_status', PaymentStatus::PENDING->value)
        ->where('updated_at', '<=', $deadline)
        ->orderBy('id')
        ->chunkById(100, function ($orders) use (&$expired) {
            foreach ($orders as $staleOrder) {
                $wasExpired = DB::transaction(function () use ($staleOrder) {
                    $order = Order::query()
                        ->whereKey($staleOrder->id)
                        ->lockForUpdate()
                        ->first();

                    if (
                        ! $order ||
                        $order->status !== OrderStatus::CREATED->value ||
                        $order->payment_status !== PaymentStatus::PENDING->value
                    ) {
                        return false;
                    }

                    $order->status = OrderStatus::CANCELED_BY_USER->value;
                    $order->payment_status = PaymentStatus::FAILED->value;
                    $order->save();

                    OrderEvent::create([
                        'order_id' => $order->id,
                        'event' => OrderStatus::CANCELED_BY_USER->value,
                        'payload' => [
                            'reason' => 'Автоматическая отмена: истекло время ожидания оплаты',
                        ],
                    ]);

                    return true;
                });

                if ($wasExpired) {
                    $expired++;
                }
            }
        });

    $this->info("Expired {$expired} stale pending payment orders.");
})->purpose('Expire created orders that stayed pending payment past the payment TTL');

Schedule::command('orders:expire-stale-pending-payments')
    ->everyFiveMinutes()
    ->withoutOverlapping();
