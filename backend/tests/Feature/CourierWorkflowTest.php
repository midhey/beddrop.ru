<?php

namespace Tests\Feature;

use App\Enums\CourierShiftStatus;
use App\Enums\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesApiData;
use Tests\TestCase;

class CourierWorkflowTest extends TestCase
{
    use CreatesApiData;
    use RefreshDatabase;

    public function test_courier_can_complete_order_lifecycle_and_close_shift(): void
    {
        $courier = $this->createUser();
        $customer = $this->createUser();
        $restaurantOwner = $this->createUser();
        $restaurant = $this->createRestaurant($restaurantOwner);
        $product = $this->createProduct($restaurant);
        $order = $this->createAcceptedOrder($customer, $restaurant, $product);

        $this->createCourierProfile($courier);

        $this->actingAs($courier, 'api')
            ->postJson('/api/v1/courier/shifts/start')
            ->assertCreated()
            ->assertJsonPath('shift.status', CourierShiftStatus::OPEN->value);

        $this->actingAs($courier, 'api')
            ->getJson('/api/v1/courier/orders/available')
            ->assertOk()
            ->assertJsonFragment(['id' => $order->id]);

        $this->actingAs($courier, 'api')
            ->postJson("/api/v1/courier/orders/{$order->id}/assign")
            ->assertOk()
            ->assertJsonPath('data.status', OrderStatus::COURIER_ASSIGNED->value);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'courier_id' => $courier->id,
            'status' => OrderStatus::COURIER_ASSIGNED->value,
        ]);

        $this->actingAs($courier, 'api')
            ->postJson('/api/v1/courier/shifts/end')
            ->assertStatus(422);

        $this->actingAs($courier, 'api')
            ->postJson("/api/v1/courier/orders/{$order->id}/picked-up")
            ->assertOk()
            ->assertJsonPath('data.status', OrderStatus::PICKED_UP->value);

        $this->actingAs($courier, 'api')
            ->postJson("/api/v1/courier/orders/{$order->id}/delivered")
            ->assertOk()
            ->assertJsonPath('data.status', OrderStatus::DELIVERED->value)
            ->assertJsonPath('data.courier_fee', '150.00');

        $this->actingAs($courier, 'api')
            ->postJson('/api/v1/courier/shifts/end')
            ->assertOk()
            ->assertJsonPath('shift.status', CourierShiftStatus::CLOSED->value);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::DELIVERED->value,
            'courier_id' => $courier->id,
        ]);

        $this->assertDatabaseHas('courier_shifts', [
            'courier_user_id' => $courier->id,
            'status' => CourierShiftStatus::CLOSED->value,
        ]);
    }

    public function test_courier_cannot_take_orders_without_open_shift(): void
    {
        $courier = $this->createUser();
        $customer = $this->createUser();
        $restaurantOwner = $this->createUser();
        $restaurant = $this->createRestaurant($restaurantOwner);
        $product = $this->createProduct($restaurant);
        $order = $this->createAcceptedOrder($customer, $restaurant, $product);

        $this->createCourierProfile($courier);

        $this->actingAs($courier, 'api')
            ->postJson("/api/v1/courier/orders/{$order->id}/assign")
            ->assertStatus(422);

        $this->assertDatabaseMissing('courier_shifts', [
            'courier_user_id' => $courier->id,
            'status' => CourierShiftStatus::OPEN->value,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::ACCEPTED_BY_RESTAURANT->value,
            'courier_id' => null,
        ]);
    }
}
