<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\RestaurantStaffRole;
use App\Models\OrderEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesApiData;
use Tests\TestCase;

class RestaurantOrderTransitionTest extends TestCase
{
    use CreatesApiData;
    use RefreshDatabase;

    public function test_manager_can_accept_created_restaurant_order(): void
    {
        $owner = $this->createUser();
        $manager = $this->createUser();
        $customer = $this->createUser();
        $restaurant = $this->createRestaurant($owner);
        $this->attachRestaurantUser($restaurant, $manager, RestaurantStaffRole::MANAGER);
        $order = $this->createAcceptedOrder($customer, $restaurant, null, [
            'status' => OrderStatus::CREATED->value,
        ]);

        $response = $this
            ->actingAs($manager, 'api')
            ->postJson("/api/v1/restaurants/{$restaurant->slug}/orders/{$order->id}/accept");

        $response
            ->assertOk()
            ->assertJsonPath('data.status', OrderStatus::ACCEPTED_BY_RESTAURANT->value);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::ACCEPTED_BY_RESTAURANT->value,
        ]);

        $event = OrderEvent::query()
            ->where('order_id', $order->id)
            ->where('event', OrderStatus::ACCEPTED_BY_RESTAURANT->value)
            ->latest('created_at')
            ->first();

        $this->assertNotNull($event);
        $this->assertSame($manager->id, $event->payload['by_user_id']);
    }

    public function test_restaurant_cannot_accept_order_in_wrong_status(): void
    {
        $owner = $this->createUser();
        $manager = $this->createUser();
        $customer = $this->createUser();
        $restaurant = $this->createRestaurant($owner);
        $this->attachRestaurantUser($restaurant, $manager, RestaurantStaffRole::MANAGER);
        $order = $this->createAcceptedOrder($customer, $restaurant);

        $response = $this
            ->actingAs($manager, 'api')
            ->postJson("/api/v1/restaurants/{$restaurant->slug}/orders/{$order->id}/accept");

        $response->assertStatus(422);
    }

    public function test_manager_can_cancel_restaurant_order_with_reason(): void
    {
        $owner = $this->createUser();
        $manager = $this->createUser();
        $customer = $this->createUser();
        $restaurant = $this->createRestaurant($owner);
        $this->attachRestaurantUser($restaurant, $manager, RestaurantStaffRole::MANAGER);
        $order = $this->createAcceptedOrder($customer, $restaurant);

        $response = $this
            ->actingAs($manager, 'api')
            ->postJson("/api/v1/restaurants/{$restaurant->slug}/orders/{$order->id}/cancel", [
                'reason' => 'Kitchen overload',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.status', OrderStatus::CANCELED_BY_RESTAURANT->value);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::CANCELED_BY_RESTAURANT->value,
        ]);

        $event = OrderEvent::query()
            ->where('order_id', $order->id)
            ->where('event', OrderStatus::CANCELED_BY_RESTAURANT->value)
            ->latest('created_at')
            ->first();

        $this->assertNotNull($event);
        $this->assertSame($manager->id, $event->payload['by_user_id']);
        $this->assertSame('Kitchen overload', $event->payload['reason']);
    }

    public function test_restaurant_cannot_cancel_completed_order(): void
    {
        $owner = $this->createUser();
        $manager = $this->createUser();
        $customer = $this->createUser();
        $restaurant = $this->createRestaurant($owner);
        $this->attachRestaurantUser($restaurant, $manager, RestaurantStaffRole::MANAGER);
        $order = $this->createAcceptedOrder($customer, $restaurant, null, [
            'status' => OrderStatus::DELIVERED->value,
        ]);

        $response = $this
            ->actingAs($manager, 'api')
            ->postJson("/api/v1/restaurants/{$restaurant->slug}/orders/{$order->id}/cancel", [
                'reason' => 'Too late',
            ]);

        $response->assertStatus(422);
    }
}
