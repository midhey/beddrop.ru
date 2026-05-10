<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesApiData;
use Tests\TestCase;

class AppShellBootstrapTest extends TestCase
{
    use CreatesApiData;
    use RefreshDatabase;

    public function test_bootstrap_returns_lightweight_shell_state(): void
    {
        $customer = $this->createUser();
        $restaurant = $this->createRestaurant($customer);
        $product = $this->createProduct($restaurant, attributes: ['price' => 250]);
        $order = $this->createAcceptedOrder($customer, $restaurant, $product, [
            'total_price' => 500,
        ]);
        $cart = $this->createActiveCart($customer, $restaurant);

        $this->addCartItem($cart, $product, 3);

        $response = $this
            ->actingAs($customer, 'api')
            ->getJson('/api/v1/me/bootstrap');

        $response
            ->assertOk()
            ->assertJsonPath('user.id', $customer->id)
            ->assertJsonPath('has_restaurants_access', true)
            ->assertJsonPath('has_courier_access', false)
            ->assertJsonPath('active_order.id', $order->id)
            ->assertJsonPath('active_order.restaurant.id', $restaurant->id)
            ->assertJsonPath('active_order.items_count', 1)
            ->assertJsonPath('cart_summary.id', $cart->id)
            ->assertJsonPath('cart_summary.is_summary', true)
            ->assertJsonPath('cart_summary.items_count', 3)
            ->assertJsonPath('cart_summary.total_price', 750);

        $this->assertArrayNotHasKey('events', $response->json('active_order'));
        $this->assertSame([], $response->json('cart_summary.items'));
    }

    public function test_active_order_endpoint_returns_null_after_final_status(): void
    {
        $customer = $this->createUser();
        $restaurant = $this->createRestaurant($this->createUser());

        $this->createAcceptedOrder($customer, $restaurant, attributes: [
            'status' => OrderStatus::DELIVERED->value,
        ]);

        $this
            ->actingAs($customer, 'api')
            ->getJson('/api/v1/orders/active')
            ->assertOk()
            ->assertJsonPath('order', null);
    }

    public function test_active_order_endpoint_returns_lightweight_active_order(): void
    {
        $customer = $this->createUser();
        $restaurant = $this->createRestaurant($this->createUser());
        $order = $this->createAcceptedOrder($customer, $restaurant);

        $response = $this
            ->actingAs($customer, 'api')
            ->getJson('/api/v1/orders/active');

        $response
            ->assertOk()
            ->assertJsonPath('order.id', $order->id)
            ->assertJsonPath('order.restaurant.id', $restaurant->id)
            ->assertJsonPath('order.items_count', 1);

        $this->assertArrayNotHasKey('items', $response->json('order'));
        $this->assertArrayNotHasKey('events', $response->json('order'));
    }
}
