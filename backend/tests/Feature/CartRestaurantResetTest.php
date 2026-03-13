<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesApiData;
use Tests\TestCase;

class CartRestaurantResetTest extends TestCase
{
    use CreatesApiData;
    use RefreshDatabase;

    public function test_user_can_switch_restaurants_after_removing_last_cart_item(): void
    {
        $customer = $this->createUser();
        $firstRestaurant = $this->createRestaurant($this->createUser());
        $secondRestaurant = $this->createRestaurant($this->createUser());
        $firstProduct = $this->createProduct($firstRestaurant);
        $secondProduct = $this->createProduct($secondRestaurant);

        $cart = $this->createActiveCart($customer, $firstRestaurant);
        $item = $this->addCartItem($cart, $firstProduct);

        $removeResponse = $this
            ->actingAs($customer, 'api')
            ->deleteJson("/api/v1/cart/items/{$item->id}");

        $removeResponse->assertOk();

        $addResponse = $this
            ->actingAs($customer, 'api')
            ->postJson('/api/v1/cart/items', [
                'product_id' => $secondProduct->id,
                'quantity' => 1,
            ]);

        $addResponse
            ->assertCreated()
            ->assertJsonPath('cart.restaurant.id', $secondRestaurant->id);
    }
}
