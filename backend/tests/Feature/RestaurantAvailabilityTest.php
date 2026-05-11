<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesApiData;
use Tests\TestCase;

class RestaurantAvailabilityTest extends TestCase
{
    use CreatesApiData;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_restaurant_accepts_order_inside_working_hours(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-11 12:00:00', 'Europe/Moscow'));

        $customer = $this->createUser();
        $restaurant = $this->createRestaurant(null, [
            'accepts_orders' => true,
            'timezone' => 'Europe/Moscow',
            'opens_at' => '10:00',
            'closes_at' => '23:00',
        ]);
        $product = $this->createProduct($restaurant);
        $cart = $this->createActiveCart($customer, $restaurant);
        $this->addCartItem($cart, $product);

        $this
            ->actingAs($customer, 'api')
            ->postJson('/api/v1/orders', [
                'payment_method' => 'CASH',
            ])
            ->assertCreated();
    }

    public function test_restaurant_rejects_order_outside_working_hours(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-11 09:00:00', 'Europe/Moscow'));

        $customer = $this->createUser();
        $restaurant = $this->createRestaurant(null, [
            'timezone' => 'Europe/Moscow',
            'opens_at' => '10:00',
            'closes_at' => '23:00',
        ]);
        $product = $this->createProduct($restaurant);
        $cart = $this->createActiveCart($customer, $restaurant);
        $this->addCartItem($cart, $product);

        $this
            ->actingAs($customer, 'api')
            ->postJson('/api/v1/orders', [
                'payment_method' => 'CASH',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Ресторан сейчас закрыт для заказов.');
    }

    public function test_manual_closure_rejects_order_regardless_of_working_hours(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-11 12:00:00', 'Europe/Moscow'));

        $customer = $this->createUser();
        $restaurant = $this->createRestaurant(null, [
            'accepts_orders' => false,
            'closed_reason' => 'Технический перерыв',
            'timezone' => 'Europe/Moscow',
            'opens_at' => '10:00',
            'closes_at' => '23:00',
        ]);
        $product = $this->createProduct($restaurant);
        $cart = $this->createActiveCart($customer, $restaurant);
        $this->addCartItem($cart, $product);

        $this
            ->actingAs($customer, 'api')
            ->postJson('/api/v1/orders', [
                'payment_method' => 'CASH',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Ресторан сейчас не принимает заказы: Технический перерыв');
    }

    public function test_inactive_restaurant_rejects_order(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-11 12:00:00', 'Europe/Moscow'));

        $customer = $this->createUser();
        $restaurant = $this->createRestaurant(null, [
            'is_active' => false,
            'timezone' => 'Europe/Moscow',
            'opens_at' => '10:00',
            'closes_at' => '23:00',
        ]);
        $product = $this->createProduct($restaurant);
        $cart = $this->createActiveCart($customer, $restaurant);
        $this->addCartItem($cart, $product);

        $this
            ->actingAs($customer, 'api')
            ->postJson('/api/v1/orders', [
                'payment_method' => 'CASH',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Ресторан сейчас недоступен для заказов.');
    }

    public function test_overnight_working_hours_are_supported(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-11 01:00:00', 'Europe/Moscow'));

        $restaurant = $this->createRestaurant(null, [
            'timezone' => 'Europe/Moscow',
            'opens_at' => '18:00',
            'closes_at' => '02:00',
        ]);

        $this->assertTrue($restaurant->isOpenForOrders());

        Carbon::setTestNow(Carbon::parse('2026-05-11 12:00:00', 'Europe/Moscow'));

        $this->assertFalse($restaurant->fresh()->isOpenForOrders());
    }

    public function test_restaurant_response_contains_availability(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-11 12:00:00', 'Europe/Moscow'));

        $restaurant = $this->createRestaurant(null, [
            'timezone' => 'Europe/Moscow',
            'opens_at' => '10:00',
            'closes_at' => '23:00',
        ]);

        $this
            ->getJson("/api/v1/restaurants/{$restaurant->slug}")
            ->assertOk()
            ->assertJsonPath('restaurant.availability.is_open', true)
            ->assertJsonPath('restaurant.availability.status', 'open')
            ->assertJsonPath('restaurant.availability.opens_at', '10:00')
            ->assertJsonPath('restaurant.availability.closes_at', '23:00');
    }
}
