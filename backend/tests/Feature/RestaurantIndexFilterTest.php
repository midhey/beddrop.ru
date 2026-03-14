<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesApiData;
use Tests\TestCase;

class RestaurantIndexFilterTest extends TestCase
{
    use CreatesApiData;
    use RefreshDatabase;

    public function test_guest_sees_all_active_restaurants_when_category_filter_is_not_passed(): void
    {
        $restaurantWithoutProducts = $this->createRestaurant();
        $restaurantWithInactiveProduct = $this->createRestaurant();
        $category = $this->createProductCategory();
        $this->createProduct($restaurantWithInactiveProduct, $category, [
            'is_active' => false,
        ]);

        $inactiveRestaurant = $this->createRestaurant(null, [
            'is_active' => false,
        ]);
        $this->createProduct($inactiveRestaurant, $category);

        $response = $this->getJson('/api/v1/restaurants');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('total', 2);

        $this->assertEqualsCanonicalizing(
            [$restaurantWithoutProducts->id, $restaurantWithInactiveProduct->id],
            collect($response->json('data'))->pluck('id')->all(),
        );
    }

    public function test_guest_can_filter_active_restaurants_by_category_id_using_only_active_products(): void
    {
        $pizzaCategory = $this->createProductCategory([
            'slug' => 'pizza',
            'name' => 'Pizza',
        ]);
        $sushiCategory = $this->createProductCategory([
            'slug' => 'sushi',
            'name' => 'Sushi',
        ]);

        $matchingRestaurant = $this->createRestaurant();
        $this->createProduct($matchingRestaurant, $pizzaCategory);

        $secondMatchingRestaurant = $this->createRestaurant();
        $this->createProduct($secondMatchingRestaurant, $pizzaCategory);

        $differentCategoryRestaurant = $this->createRestaurant();
        $this->createProduct($differentCategoryRestaurant, $sushiCategory);

        $inactiveProductRestaurant = $this->createRestaurant();
        $this->createProduct($inactiveProductRestaurant, $pizzaCategory, [
            'is_active' => false,
        ]);

        $inactiveRestaurant = $this->createRestaurant(null, [
            'is_active' => false,
        ]);
        $this->createProduct($inactiveRestaurant, $pizzaCategory);

        $response = $this->getJson("/api/v1/restaurants?category_id={$pizzaCategory->id}");

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('total', 2);

        $this->assertEqualsCanonicalizing(
            [$matchingRestaurant->id, $secondMatchingRestaurant->id],
            collect($response->json('data'))->pluck('id')->all(),
        );
    }
}
