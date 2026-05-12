<?php

namespace Tests\Feature;

use App\Enums\CourierProfileStatus;
use App\Enums\CourierShiftStatus;
use App\Enums\CourierVehicle;
use App\Enums\OrderStatus;
use App\Models\CourierShift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesApiData;
use Tests\TestCase;

class CourierProfileSecurityTest extends TestCase
{
    use CreatesApiData;
    use RefreshDatabase;

    public function test_user_created_courier_profile_is_suspended(): void
    {
        $user = $this->createUser();

        $this
            ->actingAs($user, 'api')
            ->postJson('/api/v1/courier/profile', [
                'vehicle' => CourierVehicle::BIKE->value,
            ])
            ->assertOk()
            ->assertJsonPath('profile.status', CourierProfileStatus::SUSPENDED->value)
            ->assertJsonPath('profile.vehicle', CourierVehicle::BIKE->value);

        $this->assertDatabaseHas('courier_profiles', [
            'user_id' => $user->id,
            'status' => CourierProfileStatus::SUSPENDED->value,
            'vehicle' => CourierVehicle::BIKE->value,
        ]);
    }

    public function test_active_courier_can_update_vehicle_without_losing_status(): void
    {
        $courier = $this->createUser();
        $this->createCourierProfile($courier, [
            'status' => CourierProfileStatus::ACTIVE->value,
            'vehicle' => CourierVehicle::BIKE->value,
        ]);

        $this
            ->actingAs($courier, 'api')
            ->postJson('/api/v1/courier/profile', [
                'vehicle' => CourierVehicle::CAR->value,
            ])
            ->assertOk()
            ->assertJsonPath('profile.status', CourierProfileStatus::ACTIVE->value)
            ->assertJsonPath('profile.vehicle', CourierVehicle::CAR->value);

        $this->assertDatabaseHas('courier_profiles', [
            'user_id' => $courier->id,
            'status' => CourierProfileStatus::ACTIVE->value,
            'vehicle' => CourierVehicle::CAR->value,
        ]);
    }

    public function test_suspended_courier_cannot_start_shift_or_assign_order(): void
    {
        $courier = $this->createUser();
        $customer = $this->createUser();
        $restaurantOwner = $this->createUser();
        $restaurant = $this->createRestaurant($restaurantOwner);
        $product = $this->createProduct($restaurant);
        $order = $this->createAcceptedOrder($customer, $restaurant, $product, [
            'status' => OrderStatus::READY_FOR_PICKUP->value,
        ]);

        $this->createCourierProfile($courier, [
            'status' => CourierProfileStatus::SUSPENDED->value,
        ]);

        $this
            ->actingAs($courier, 'api')
            ->postJson('/api/v1/courier/shifts/start')
            ->assertForbidden();

        CourierShift::create([
            'courier_user_id' => $courier->id,
            'started_at' => now(),
            'status' => CourierShiftStatus::OPEN->value,
        ]);

        $this
            ->actingAs($courier, 'api')
            ->postJson("/api/v1/courier/orders/{$order->id}/assign")
            ->assertForbidden();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::READY_FOR_PICKUP->value,
            'courier_id' => null,
        ]);
    }

    public function test_admin_can_activate_courier_profile(): void
    {
        $admin = $this->createUser(['is_admin' => true]);
        $courier = $this->createUser();
        $this->createCourierProfile($courier, [
            'status' => CourierProfileStatus::SUSPENDED->value,
        ]);

        $this
            ->actingAs($admin, 'api')
            ->patchJson("/api/v1/admin/couriers/{$courier->id}", [
                'status' => CourierProfileStatus::ACTIVE->value,
            ])
            ->assertOk()
            ->assertJsonPath('courier.status', CourierProfileStatus::ACTIVE->value);

        $this->assertDatabaseHas('courier_profiles', [
            'user_id' => $courier->id,
            'status' => CourierProfileStatus::ACTIVE->value,
        ]);
    }
}
