<?php

namespace Tests\Feature;

use App\Enums\RestaurantStaffRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesApiData;
use Tests\TestCase;

class RestaurantStaffAccessTest extends TestCase
{
    use CreatesApiData;
    use RefreshDatabase;

    public function test_unrelated_user_cannot_list_restaurant_staff(): void
    {
        $owner = $this->createUser();
        $outsider = $this->createUser();
        $restaurant = $this->createRestaurant($owner);

        $response = $this
            ->actingAs($outsider, 'api')
            ->getJson("/api/v1/restaurants/{$restaurant->slug}/users");

        $response->assertForbidden();
    }

    public function test_manager_can_list_restaurant_staff(): void
    {
        $owner = $this->createUser();
        $manager = $this->createUser();
        $restaurant = $this->createRestaurant($owner);

        $this->attachRestaurantUser($restaurant, $manager, RestaurantStaffRole::MANAGER);

        $response = $this
            ->actingAs($manager, 'api')
            ->getJson("/api/v1/restaurants/{$restaurant->slug}/users");

        $response
            ->assertOk()
            ->assertJsonFragment([
                'id' => $owner->id,
                'role' => RestaurantStaffRole::OWNER->value,
            ]);
    }

    public function test_manager_cannot_promote_staff_member_to_owner(): void
    {
        $owner = $this->createUser();
        $manager = $this->createUser();
        $staffMember = $this->createUser();
        $restaurant = $this->createRestaurant($owner);

        $this->attachRestaurantUser($restaurant, $manager, RestaurantStaffRole::MANAGER);
        $this->attachRestaurantUser($restaurant, $staffMember, RestaurantStaffRole::STAFF);

        $response = $this
            ->actingAs($manager, 'api')
            ->putJson("/api/v1/restaurants/{$restaurant->slug}/users/{$staffMember->id}", [
                'role' => RestaurantStaffRole::OWNER->value,
            ]);

        $response->assertForbidden();
    }

    public function test_manager_cannot_delete_current_restaurant_owner(): void
    {
        $owner = $this->createUser();
        $manager = $this->createUser();
        $restaurant = $this->createRestaurant($owner);

        $this->attachRestaurantUser($restaurant, $manager, RestaurantStaffRole::MANAGER);

        $response = $this
            ->actingAs($manager, 'api')
            ->deleteJson("/api/v1/restaurants/{$restaurant->slug}/users/{$owner->id}");

        $response->assertStatus(422);
    }

    public function test_manager_cannot_demote_current_restaurant_owner(): void
    {
        $owner = $this->createUser();
        $manager = $this->createUser();
        $restaurant = $this->createRestaurant($owner);

        $this->attachRestaurantUser($restaurant, $manager, RestaurantStaffRole::MANAGER);

        $response = $this
            ->actingAs($manager, 'api')
            ->putJson("/api/v1/restaurants/{$restaurant->slug}/users/{$owner->id}", [
                'role' => RestaurantStaffRole::MANAGER->value,
            ]);

        $response->assertStatus(422);
    }
}
