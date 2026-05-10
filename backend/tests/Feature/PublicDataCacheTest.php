<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesApiData;
use Tests\TestCase;

class PublicDataCacheTest extends TestCase
{
    use CreatesApiData;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_restaurant_detail_cache_is_invalidated_after_restaurant_update(): void
    {
        $owner = $this->createUser();
        $restaurant = $this->createRestaurant($owner, [
            'name' => 'Old name',
        ]);

        $this->getJson("/api/v1/restaurants/{$restaurant->slug}")
            ->assertOk()
            ->assertJsonPath('restaurant.name', 'Old name');

        DB::table('restaurants')
            ->where('id', $restaurant->id)
            ->update([
                'name' => 'Direct database name',
                'updated_at' => now(),
            ]);

        $this->getJson("/api/v1/restaurants/{$restaurant->slug}")
            ->assertOk()
            ->assertJsonPath('restaurant.name', 'Old name');

        $this
            ->actingAs($owner, 'api')
            ->putJson("/api/v1/restaurants/{$restaurant->id}", [
                'name' => 'New name',
            ])
            ->assertOk();

        $this->getJson("/api/v1/restaurants/{$restaurant->slug}")
            ->assertOk()
            ->assertJsonPath('restaurant.name', 'New name');
    }

    public function test_restaurant_index_cache_is_invalidated_after_product_visibility_update(): void
    {
        $owner = $this->createUser();
        $category = $this->createProductCategory();
        $restaurant = $this->createRestaurant($owner);
        $product = $this->createProduct($restaurant, $category, [
            'is_active' => false,
        ]);

        $this->getJson("/api/v1/restaurants?category_id={$category->id}")
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this
            ->actingAs($owner, 'api')
            ->putJson("/api/v1/restaurants/{$restaurant->slug}/products/{$product->id}", [
                'is_active' => true,
            ])
            ->assertOk();

        $this->getJson("/api/v1/restaurants?category_id={$category->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $restaurant->id);
    }

    public function test_restaurant_menu_cache_is_invalidated_after_product_update(): void
    {
        $owner = $this->createUser();
        $restaurant = $this->createRestaurant($owner);
        $product = $this->createProduct($restaurant, null, [
            'name' => 'Old product',
        ]);

        $this->getJson("/api/v1/restaurants/{$restaurant->slug}/products")
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Old product');

        $this
            ->actingAs($owner, 'api')
            ->putJson("/api/v1/restaurants/{$restaurant->slug}/products/{$product->id}", [
                'name' => 'New product',
            ])
            ->assertOk();

        $this->getJson("/api/v1/restaurants/{$restaurant->slug}/products")
            ->assertOk()
            ->assertJsonPath('data.0.name', 'New product');
    }

    public function test_category_cache_is_invalidated_after_category_update(): void
    {
        $admin = $this->createUser(['is_admin' => true]);
        $category = $this->createProductCategory([
            'name' => 'Old category',
        ]);

        $this->getJson('/api/v1/product-categories')
            ->assertOk()
            ->assertJsonPath('categories.0.name', 'Old category');

        $this
            ->actingAs($admin, 'api')
            ->putJson("/api/v1/product-categories/{$category->id}", [
                'name' => 'New category',
            ])
            ->assertOk();

        $this->getJson('/api/v1/product-categories')
            ->assertOk()
            ->assertJsonPath('categories.0.name', 'New category');
    }

    public function test_restaurant_menu_cache_is_invalidated_after_category_update(): void
    {
        $admin = $this->createUser(['is_admin' => true]);
        $category = $this->createProductCategory([
            'name' => 'Old category',
        ]);
        $restaurant = $this->createRestaurant();
        $this->createProduct($restaurant, $category);

        $this->getJson("/api/v1/restaurants/{$restaurant->slug}/products")
            ->assertOk()
            ->assertJsonPath('data.0.category.name', 'Old category');

        $this
            ->actingAs($admin, 'api')
            ->putJson("/api/v1/product-categories/{$category->id}", [
                'name' => 'New category',
            ])
            ->assertOk();

        $this->getJson("/api/v1/restaurants/{$restaurant->slug}/products")
            ->assertOk()
            ->assertJsonPath('data.0.category.name', 'New category');
    }
}
