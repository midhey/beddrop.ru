<?php

namespace Tests\Feature;

use App\Enums\RestaurantStaffRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesApiData;
use Tests\TestCase;

class ProductVisibilityTest extends TestCase
{
    use CreatesApiData;
    use RefreshDatabase;

    public function test_guest_cannot_list_products_of_inactive_restaurant(): void
    {
        $owner = $this->createUser();
        $restaurant = $this->createRestaurant($owner, ['is_active' => false]);
        $this->createProduct($restaurant);

        $this->getJson("/api/v1/restaurants/{$restaurant->slug}/products")
            ->assertForbidden();
    }

    public function test_staff_can_list_products_of_inactive_restaurant(): void
    {
        $owner = $this->createUser();
        $staff = $this->createUser();
        $restaurant = $this->createRestaurant($owner, ['is_active' => false]);
        $activeProduct = $this->createProduct($restaurant);
        $inactiveProduct = $this->createProduct($restaurant, null, [
            'is_active' => false,
        ]);

        $this->attachRestaurantUser($restaurant, $staff, RestaurantStaffRole::STAFF);

        $this->actingAs($staff, 'api')
            ->getJson("/api/v1/restaurants/{$restaurant->slug}/products")
            ->assertOk()
            ->assertJsonFragment(['id' => $activeProduct->id])
            ->assertJsonFragment(['id' => $inactiveProduct->id]);
    }

    public function test_guest_cannot_view_inactive_product_directly(): void
    {
        $owner = $this->createUser();
        $restaurant = $this->createRestaurant($owner);
        $product = $this->createProduct($restaurant, null, [
            'is_active' => false,
        ]);

        $this->getJson("/api/v1/restaurants/{$restaurant->slug}/products/{$product->id}")
            ->assertNotFound();
    }

    public function test_staff_can_view_inactive_product_directly(): void
    {
        $owner = $this->createUser();
        $staff = $this->createUser();
        $restaurant = $this->createRestaurant($owner);
        $product = $this->createProduct($restaurant, null, [
            'is_active' => false,
        ]);

        $this->attachRestaurantUser($restaurant, $staff, RestaurantStaffRole::STAFF);

        $this->actingAs($staff, 'api')
            ->getJson("/api/v1/restaurants/{$restaurant->slug}/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $product->id);
    }

    public function test_guest_cannot_view_product_inside_inactive_restaurant(): void
    {
        $owner = $this->createUser();
        $restaurant = $this->createRestaurant($owner, ['is_active' => false]);
        $product = $this->createProduct($restaurant);

        $this->getJson("/api/v1/restaurants/{$restaurant->slug}/products/{$product->id}")
            ->assertForbidden();
    }

    public function test_staff_can_view_product_inside_inactive_restaurant(): void
    {
        $owner = $this->createUser();
        $staff = $this->createUser();
        $restaurant = $this->createRestaurant($owner, ['is_active' => false]);
        $product = $this->createProduct($restaurant);

        $this->attachRestaurantUser($restaurant, $staff, RestaurantStaffRole::STAFF);

        $this->actingAs($staff, 'api')
            ->getJson("/api/v1/restaurants/{$restaurant->slug}/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $product->id);
    }
}
