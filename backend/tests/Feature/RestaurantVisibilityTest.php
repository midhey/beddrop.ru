<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesApiData;
use Tests\TestCase;

class RestaurantVisibilityTest extends TestCase
{
    use CreatesApiData;
    use RefreshDatabase;

    public function test_guest_cannot_view_inactive_restaurant(): void
    {
        $owner = $this->createUser();
        $restaurant = $this->createRestaurant($owner, ['is_active' => false]);

        $response = $this->getJson("/api/v1/restaurants/{$restaurant->slug}");

        $response->assertForbidden();
    }

    public function test_owner_can_view_inactive_restaurant(): void
    {
        $owner = $this->createUser();
        $restaurant = $this->createRestaurant($owner, ['is_active' => false]);

        $response = $this
            ->actingAs($owner, 'api')
            ->getJson("/api/v1/restaurants/{$restaurant->slug}");

        $response
            ->assertOk()
            ->assertJsonPath('restaurant.id', $restaurant->id);
    }
}
