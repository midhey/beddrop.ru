<?php

namespace Tests\Feature;

use App\Enums\RestaurantStaffRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesApiData;
use Tests\TestCase;

class RestaurantManageRoleVisibilityTest extends TestCase
{
    use CreatesApiData;
    use RefreshDatabase;

    public function test_restaurant_show_returns_current_user_role_for_authenticated_staff(): void
    {
        $owner = $this->createUser();
        $manager = $this->createUser();
        $restaurant = $this->createRestaurant($owner);

        $this->attachRestaurantUser($restaurant, $manager, RestaurantStaffRole::MANAGER);

        $this->actingAs($manager, 'api')
            ->getJson("/api/v1/restaurants/{$restaurant->slug}")
            ->assertOk()
            ->assertJsonPath('restaurant.current_user_role', RestaurantStaffRole::MANAGER->value);
    }
}
