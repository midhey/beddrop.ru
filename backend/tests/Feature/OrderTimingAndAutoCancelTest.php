<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\OrderEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesApiData;
use Tests\TestCase;

class OrderTimingAndAutoCancelTest extends TestCase
{
    use CreatesApiData;
    use RefreshDatabase;

    public function test_paid_order_gets_delivery_estimates_from_payment_time(): void
    {
        $customer = $this->createUser();
        $owner = $this->createUser();
        $restaurant = $this->createRestaurant($owner);
        $order = $this->createAcceptedOrder($customer, $restaurant, null, [
            'status' => OrderStatus::CREATED->value,
            'payment_status' => PaymentStatus::PENDING->value,
            'estimated_pickup_at' => null,
            'estimated_delivery_at' => null,
            'logistics_snapshot_json' => [
                'time' => [
                    'prep' => 20,
                    'pickup_buffer' => 5,
                    'delivery' => 15,
                    'buffer' => 10,
                    'total' => 50,
                ],
            ],
        ]);

        $this->travelTo('2026-05-14 12:00:00');

        $order->payment_status = PaymentStatus::PAID->value;
        $order->save();
        $order->refresh();

        $this->assertSame('2026-05-14 12:25:00', $order->estimated_pickup_at?->format('Y-m-d H:i:s'));
        $this->assertSame('2026-05-14 12:50:00', $order->estimated_delivery_at?->format('Y-m-d H:i:s'));
    }

    public function test_command_cancels_stale_paid_created_orders(): void
    {
        $customer = $this->createUser();
        $owner = $this->createUser();
        $restaurant = $this->createRestaurant($owner);
        $staleOrder = $this->createAcceptedOrder($customer, $restaurant, null, [
            'status' => OrderStatus::CREATED->value,
            'payment_status' => PaymentStatus::PAID->value,
        ]);
        $recentOrder = $this->createAcceptedOrder($customer, $restaurant, null, [
            'status' => OrderStatus::CREATED->value,
            'payment_status' => PaymentStatus::PAID->value,
        ]);
        $pendingOrder = $this->createAcceptedOrder($customer, $restaurant, null, [
            'status' => OrderStatus::CREATED->value,
            'payment_status' => PaymentStatus::PENDING->value,
        ]);

        $this->travelTo('2026-05-14 12:00:00');

        DB::table('orders')
            ->where('id', $staleOrder->id)
            ->update(['updated_at' => now()->subHours(3)]);
        DB::table('orders')
            ->whereIn('id', [$recentOrder->id, $pendingOrder->id])
            ->update(['updated_at' => now()->subHour()]);

        $this->artisan('orders:cancel-stale-restaurant-acceptance')
            ->expectsOutput('Canceled 1 stale paid orders.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('orders', [
            'id' => $staleOrder->id,
            'status' => OrderStatus::CANCELED_BY_RESTAURANT->value,
        ]);
        $this->assertDatabaseHas('orders', [
            'id' => $recentOrder->id,
            'status' => OrderStatus::CREATED->value,
        ]);
        $this->assertDatabaseHas('orders', [
            'id' => $pendingOrder->id,
            'status' => OrderStatus::CREATED->value,
        ]);

        $event = OrderEvent::query()
            ->where('order_id', $staleOrder->id)
            ->where('event', OrderStatus::CANCELED_BY_RESTAURANT->value)
            ->first();

        $this->assertNotNull($event);
        $this->assertSame('Автоматическая отмена: ресторан не принял заказ', $event->payload['reason']);
    }

    public function test_command_expires_stale_pending_created_orders(): void
    {
        config(['orders.pending_payment_ttl_minutes' => 30]);

        $customer = $this->createUser();
        $owner = $this->createUser();
        $restaurant = $this->createRestaurant($owner);
        $staleOrder = $this->createAcceptedOrder($customer, $restaurant, null, [
            'status' => OrderStatus::CREATED->value,
            'payment_status' => PaymentStatus::PENDING->value,
        ]);
        $recentOrder = $this->createAcceptedOrder($customer, $restaurant, null, [
            'status' => OrderStatus::CREATED->value,
            'payment_status' => PaymentStatus::PENDING->value,
        ]);
        $paidOrder = $this->createAcceptedOrder($customer, $restaurant, null, [
            'status' => OrderStatus::CREATED->value,
            'payment_status' => PaymentStatus::PAID->value,
        ]);

        $this->travelTo('2026-05-14 12:00:00');

        DB::table('orders')
            ->whereIn('id', [$staleOrder->id, $paidOrder->id])
            ->update(['updated_at' => now()->subMinutes(31)]);
        DB::table('orders')
            ->where('id', $recentOrder->id)
            ->update(['updated_at' => now()->subMinutes(10)]);

        $this->artisan('orders:expire-stale-pending-payments')
            ->expectsOutput('Expired 1 stale pending payment orders.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('orders', [
            'id' => $staleOrder->id,
            'status' => OrderStatus::CANCELED_BY_USER->value,
            'payment_status' => PaymentStatus::FAILED->value,
        ]);
        $this->assertDatabaseHas('orders', [
            'id' => $recentOrder->id,
            'status' => OrderStatus::CREATED->value,
            'payment_status' => PaymentStatus::PENDING->value,
        ]);
        $this->assertDatabaseHas('orders', [
            'id' => $paidOrder->id,
            'status' => OrderStatus::CREATED->value,
            'payment_status' => PaymentStatus::PAID->value,
        ]);

        $event = OrderEvent::query()
            ->where('order_id', $staleOrder->id)
            ->where('event', OrderStatus::CANCELED_BY_USER->value)
            ->first();

        $this->assertNotNull($event);
        $this->assertSame('Автоматическая отмена: истекло время ожидания оплаты', $event->payload['reason']);
    }
}
