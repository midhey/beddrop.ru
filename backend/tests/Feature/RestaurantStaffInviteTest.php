<?php

namespace Tests\Feature;

use App\Enums\RestaurantStaffRole;
use App\Models\RestaurantStaffInvite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\CreatesApiData;
use Tests\TestCase;

class RestaurantStaffInviteTest extends TestCase
{
    use CreatesApiData;
    use RefreshDatabase;

    public function test_owner_can_create_staff_invite(): void
    {
        $owner = $this->createUser();
        $restaurant = $this->createRestaurant($owner);

        $this->actingAs($owner, 'api')
            ->postJson("/api/v1/restaurants/{$restaurant->slug}/staff-invites", [
                'role' => RestaurantStaffRole::STAFF->value,
                'expires_in_minutes' => 5,
            ])
            ->assertCreated()
            ->assertJsonPath('invite.role', RestaurantStaffRole::STAFF->value)
            ->assertJsonPath('invite.restaurant.slug', $restaurant->slug);

        $this->assertDatabaseCount('restaurant_staff_invites', 1);
    }

    public function test_manager_cannot_create_staff_invite(): void
    {
        $owner = $this->createUser();
        $manager = $this->createUser();
        $restaurant = $this->createRestaurant($owner);
        $this->attachRestaurantUser($restaurant, $manager, RestaurantStaffRole::MANAGER);

        $this->actingAs($manager, 'api')
            ->postJson("/api/v1/restaurants/{$restaurant->slug}/staff-invites", [
                'role' => RestaurantStaffRole::STAFF->value,
                'expires_in_minutes' => 5,
            ])
            ->assertForbidden();
    }

    public function test_owner_can_accept_valid_invite(): void
    {
        $owner = $this->createUser();
        $candidate = $this->createUser();
        $restaurant = $this->createRestaurant($owner);

        $invite = RestaurantStaffInvite::create([
            'restaurant_id' => $restaurant->id,
            'invited_by_user_id' => $owner->id,
            'token' => 'test-token',
            'role' => RestaurantStaffRole::STAFF->value,
            'expires_at' => Carbon::now()->addMinutes(5),
        ]);

        $this->actingAs($candidate, 'api')
            ->postJson("/api/v1/staff-invites/{$invite->token}/accept")
            ->assertOk()
            ->assertJsonPath('invite.accepted_by.id', $candidate->id);

        $this->assertDatabaseHas('restaurant_user', [
            'restaurant_id' => $restaurant->id,
            'user_id' => $candidate->id,
            'role' => RestaurantStaffRole::STAFF->value,
        ]);
    }

    public function test_expired_invite_cannot_be_accepted(): void
    {
        $owner = $this->createUser();
        $candidate = $this->createUser();
        $restaurant = $this->createRestaurant($owner);

        $invite = RestaurantStaffInvite::create([
            'restaurant_id' => $restaurant->id,
            'invited_by_user_id' => $owner->id,
            'token' => 'expired-token',
            'role' => RestaurantStaffRole::STAFF->value,
            'expires_at' => Carbon::now()->subMinute(),
        ]);

        $this->actingAs($candidate, 'api')
            ->postJson("/api/v1/staff-invites/{$invite->token}/accept")
            ->assertStatus(410);
    }
}
