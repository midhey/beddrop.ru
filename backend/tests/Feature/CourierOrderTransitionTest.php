<?php

namespace Tests\Feature;

use App\Enums\CourierShiftStatus;
use App\Enums\OrderStatus;
use App\Models\CourierShift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesApiData;
use Tests\TestCase;

class CourierOrderTransitionTest extends TestCase
{
    use CreatesApiData;
    use RefreshDatabase;

    public function test_courier_cannot_assign_order_in_invalid_status(): void
    {
        $courier = $this->createUser();
        $customer = $this->createUser();
        $restaurantOwner = $this->createUser();
        $restaurant = $this->createRestaurant($restaurantOwner);
        $product = $this->createProduct($restaurant);
        $order = $this->createAcceptedOrder($customer, $restaurant, $product, [
            'status' => OrderStatus::COURIER_ASSIGNED->value,
        ]);

        $this->createCourierProfile($courier);
        $this->openShift($courier);

        $this->actingAs($courier, 'api')
            ->postJson("/api/v1/courier/orders/{$order->id}/assign")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Этот заказ нельзя взять в работу');
    }

    public function test_assigned_courier_can_mark_order_picked_up(): void
    {
        $courier = $this->createUser();
        $customer = $this->createUser();
        $restaurantOwner = $this->createUser();
        $restaurant = $this->createRestaurant($restaurantOwner);
        $product = $this->createProduct($restaurant);

        $this->createCourierProfile($courier);

        $order = $this->createAcceptedOrder($customer, $restaurant, $product, [
            'courier_id' => $courier->id,
            'status' => OrderStatus::COURIER_ASSIGNED->value,
        ]);

        $this->openShift($courier);

        $this->actingAs($courier, 'api')
            ->postJson("/api/v1/courier/orders/{$order->id}/picked-up")
            ->assertOk()
            ->assertJsonPath('data.status', OrderStatus::PICKED_UP->value);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::PICKED_UP->value,
        ]);

        $this->assertDatabaseHas('order_events', [
            'order_id' => $order->id,
            'event' => OrderStatus::PICKED_UP->value,
        ]);
    }

    public function test_other_courier_cannot_mark_order_picked_up(): void
    {
        $assignedCourier = $this->createUser();
        $otherCourier = $this->createUser();
        $customer = $this->createUser();
        $restaurantOwner = $this->createUser();
        $restaurant = $this->createRestaurant($restaurantOwner);
        $product = $this->createProduct($restaurant);

        $this->createCourierProfile($assignedCourier);
        $this->createCourierProfile($otherCourier);

        $order = $this->createAcceptedOrder($customer, $restaurant, $product, [
            'courier_id' => $assignedCourier->id,
            'status' => OrderStatus::COURIER_ASSIGNED->value,
        ]);

        $this->openShift($otherCourier);

        $this->actingAs($otherCourier, 'api')
            ->postJson("/api/v1/courier/orders/{$order->id}/picked-up")
            ->assertForbidden();
    }

    public function test_courier_cannot_mark_order_picked_up_in_invalid_status(): void
    {
        $courier = $this->createUser();
        $customer = $this->createUser();
        $restaurantOwner = $this->createUser();
        $restaurant = $this->createRestaurant($restaurantOwner);
        $product = $this->createProduct($restaurant);

        $this->createCourierProfile($courier);

        $order = $this->createAcceptedOrder($customer, $restaurant, $product, [
            'courier_id' => $courier->id,
            'status' => OrderStatus::PICKED_UP->value,
        ]);

        $this->openShift($courier);

        $this->actingAs($courier, 'api')
            ->postJson("/api/v1/courier/orders/{$order->id}/picked-up")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Нельзя перевести заказ в статус PICKED_UP');
    }

    public function test_assigned_courier_can_mark_order_delivered_and_record_fee(): void
    {
        $courier = $this->createUser();
        $customer = $this->createUser();
        $restaurantOwner = $this->createUser();
        $restaurant = $this->createRestaurant($restaurantOwner);
        $product = $this->createProduct($restaurant);

        $this->createCourierProfile($courier);

        $order = $this->createAcceptedOrder($customer, $restaurant, $product, [
            'courier_id' => $courier->id,
            'status' => OrderStatus::PICKED_UP->value,
        ]);

        $this->openShift($courier);

        $this->actingAs($courier, 'api')
            ->postJson("/api/v1/courier/orders/{$order->id}/delivered")
            ->assertOk()
            ->assertJsonPath('data.status', OrderStatus::DELIVERED->value)
            ->assertJsonPath('data.courier_fee', '150.00');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::DELIVERED->value,
            'courier_fee' => 150,
        ]);

        $this->assertDatabaseHas('order_events', [
            'order_id' => $order->id,
            'event' => OrderStatus::DELIVERED->value,
        ]);
    }

    public function test_other_courier_cannot_mark_order_delivered(): void
    {
        $assignedCourier = $this->createUser();
        $otherCourier = $this->createUser();
        $customer = $this->createUser();
        $restaurantOwner = $this->createUser();
        $restaurant = $this->createRestaurant($restaurantOwner);
        $product = $this->createProduct($restaurant);

        $this->createCourierProfile($assignedCourier);
        $this->createCourierProfile($otherCourier);

        $order = $this->createAcceptedOrder($customer, $restaurant, $product, [
            'courier_id' => $assignedCourier->id,
            'status' => OrderStatus::PICKED_UP->value,
        ]);

        $this->openShift($otherCourier);

        $this->actingAs($otherCourier, 'api')
            ->postJson("/api/v1/courier/orders/{$order->id}/delivered")
            ->assertForbidden();
    }

    public function test_courier_cannot_mark_order_delivered_in_invalid_status(): void
    {
        $courier = $this->createUser();
        $customer = $this->createUser();
        $restaurantOwner = $this->createUser();
        $restaurant = $this->createRestaurant($restaurantOwner);
        $product = $this->createProduct($restaurant);

        $this->createCourierProfile($courier);

        $order = $this->createAcceptedOrder($customer, $restaurant, $product, [
            'courier_id' => $courier->id,
            'status' => OrderStatus::COURIER_ASSIGNED->value,
        ]);

        $this->openShift($courier);

        $this->actingAs($courier, 'api')
            ->postJson("/api/v1/courier/orders/{$order->id}/delivered")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Нельзя перевести заказ в статус DELIVERED');
    }

    private function openShift($courier): CourierShift
    {
        return CourierShift::create([
            'courier_user_id' => $courier->id,
            'started_at' => now(),
            'status' => CourierShiftStatus::OPEN->value,
        ]);
    }
}
