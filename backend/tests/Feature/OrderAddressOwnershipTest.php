<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
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
                'payment_method' => PaymentMethod::CASH->value,
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
                'payment_method' => PaymentMethod::CASH->value,
            ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('orders', [
            'user_id' => $customer->id,
            'delivery_address_id' => $foreignAddress->id,
        ]);
    }
}
