<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesApiData;
use Tests\TestCase;

class OrderAddressOwnershipTest extends TestCase
{
    use CreatesApiData;
    use RefreshDatabase;

    public function test_user_can_create_order_with_owned_delivery_address(): void
    {
        $customer = $this->createUser();
        $restaurantOwner = $this->createUser();
        $restaurant = $this->createRestaurant($restaurantOwner);
        $product = $this->createProduct($restaurant);
        $cart = $this->createActiveCart($customer, $restaurant);
        $this->addCartItem($cart, $product, 2);
        $address = $this->createAddress($customer);

        $response = $this
            ->actingAs($customer, 'api')
            ->postJson('/api/v1/orders', [
                'delivery_address_id' => $address->id,
                'payment_method' => PaymentMethod::ONLINE->value,
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('orders', [
            'user_id' => $customer->id,
            'delivery_address_id' => $address->id,
        ]);
    }

    public function test_user_cannot_create_order_with_foreign_delivery_address(): void
    {
        $customer = $this->createUser();
        $foreignUser = $this->createUser();
        $restaurantOwner = $this->createUser();
        $restaurant = $this->createRestaurant($restaurantOwner);
        $product = $this->createProduct($restaurant);
        $cart = $this->createActiveCart($customer, $restaurant);
        $this->addCartItem($cart, $product);
        $foreignAddress = $this->createAddress($foreignUser);

        $response = $this
            ->actingAs($customer, 'api')
            ->postJson('/api/v1/orders', [
                'delivery_address_id' => $foreignAddress->id,
                'payment_method' => PaymentMethod::ONLINE->value,
            ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('orders', [
            'user_id' => $customer->id,
            'delivery_address_id' => $foreignAddress->id,
        ]);
    }

    public function test_active_cart_can_only_be_consumed_once(): void
    {
        $customer = $this->createUser();
        $restaurantOwner = $this->createUser();
        $restaurant = $this->createRestaurant($restaurantOwner);
        $product = $this->createProduct($restaurant);
        $cart = $this->createActiveCart($customer, $restaurant);
        $this->addCartItem($cart, $product);
        $address = $this->createAddress($customer);

        $payload = [
            'delivery_address_id' => $address->id,
            'payment_method' => PaymentMethod::ONLINE->value,
        ];

        $this
            ->actingAs($customer, 'api')
            ->postJson('/api/v1/orders', $payload)
            ->assertCreated();

        $this
            ->actingAs($customer, 'api')
            ->postJson('/api/v1/orders', $payload)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Активная корзина пуста.');

        $this->assertSame(1, Order::query()->where('user_id', $customer->id)->count());
    }
}
