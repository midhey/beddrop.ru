<?php

namespace Tests\Feature;

use App\Enums\RestaurantStaffRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesApiData;
use Tests\TestCase;

class RestaurantOwnerSecurityTest extends TestCase
{
    use CreatesApiData;
    use RefreshDatabase;

    public function test_regular_user_cannot_assign_another_owner_when_creating_restaurant(): void
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();

        $response = $this
            ->actingAs($user, 'api')
            ->postJson('/api/v1/restaurants', [
                'name' => 'Security Test Restaurant',
                'owner_id' => $otherUser->id,
                'address' => [
                    'value' => 'Moscow, Security Test Street, 1',
                    'lat' => 55.75,
                    'lng' => 37.61,
                ],
            ])
            ->assertCreated();

        $restaurantId = $response->json('restaurant.id');

        $this->assertDatabaseHas('restaurant_user', [
            'restaurant_id' => $restaurantId,
            'user_id' => $user->id,
            'role' => RestaurantStaffRole::OWNER->value,
        ]);

        $this->assertDatabaseMissing('restaurant_user', [
            'restaurant_id' => $restaurantId,
            'user_id' => $otherUser->id,
            'role' => RestaurantStaffRole::OWNER->value,
        ]);
    }

    public function test_admin_can_assign_owner_when_creating_restaurant(): void
    {
        $admin = $this->createUser(['is_admin' => true]);
        $owner = $this->createUser();

        $response = $this
            ->actingAs($admin, 'api')
            ->postJson('/api/v1/restaurants', [
                'name' => 'Admin Owner Restaurant',
                'owner_id' => $owner->id,
                'address' => [
                    'value' => 'Moscow, Admin Owner Street, 1',
                    'lat' => 55.76,
                    'lng' => 37.62,
                ],
            ])
            ->assertCreated();

        $restaurantId = $response->json('restaurant.id');

        $this->assertDatabaseHas('restaurant_user', [
            'restaurant_id' => $restaurantId,
            'user_id' => $owner->id,
            'role' => RestaurantStaffRole::OWNER->value,
        ]);

        $this->assertDatabaseMissing('restaurant_user', [
            'restaurant_id' => $restaurantId,
            'user_id' => $admin->id,
            'role' => RestaurantStaffRole::OWNER->value,
        ]);
    }
}
