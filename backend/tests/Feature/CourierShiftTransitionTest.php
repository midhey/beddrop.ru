<?php

namespace Tests\Feature;

use App\Enums\CourierProfileStatus;
use App\Enums\CourierShiftStatus;
use App\Enums\OrderStatus;
use App\Models\CourierShift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesApiData;
use Tests\TestCase;

class CourierShiftTransitionTest extends TestCase
{
    use CreatesApiData;
    use RefreshDatabase;

    public function test_courier_cannot_start_second_open_shift(): void
    {
        $courier = $this->createUser();
        $this->createCourierProfile($courier);

        $openShift = CourierShift::create([
            'courier_user_id' => $courier->id,
            'started_at' => now(),
            'status' => CourierShiftStatus::OPEN->value,
        ]);

        $this->actingAs($courier, 'api')
            ->postJson('/api/v1/courier/shifts/start')
            ->assertStatus(422)
            ->assertJsonPath('message', 'У вас уже есть открытая смена')
            ->assertJsonPath('shift.id', $openShift->id);
    }

    public function test_courier_cannot_end_shift_without_open_shift(): void
    {
        $courier = $this->createUser();
        $this->createCourierProfile($courier);

        $this->actingAs($courier, 'api')
            ->postJson('/api/v1/courier/shifts/end')
            ->assertStatus(422)
            ->assertJsonPath('message', 'Нет открытой смены');
    }

    public function test_courier_cannot_end_shift_with_active_orders(): void
    {
        $courier = $this->createUser();
        $customer = $this->createUser();
        $restaurantOwner = $this->createUser();
        $restaurant = $this->createRestaurant($restaurantOwner);
        $product = $this->createProduct($restaurant);

        $this->createCourierProfile($courier);

        $shift = CourierShift::create([
            'courier_user_id' => $courier->id,
            'started_at' => now(),
            'status' => CourierShiftStatus::OPEN->value,
        ]);

        $this->createAcceptedOrder($customer, $restaurant, $product, [
            'courier_id' => $courier->id,
            'status' => OrderStatus::COURIER_ASSIGNED->value,
        ]);

        $this->actingAs($courier, 'api')
            ->postJson('/api/v1/courier/shifts/end')
            ->assertStatus(422)
            ->assertJsonPath('message', 'Нельзя закрыть смену: у вас есть незавершённые заказы.');

        $this->assertDatabaseHas('courier_shifts', [
            'id' => $shift->id,
            'status' => CourierShiftStatus::OPEN->value,
        ]);
    }

    public function test_disabled_courier_cannot_start_shift(): void
    {
        $courier = $this->createUser();
        $this->createCourierProfile($courier, [
            'status' => CourierProfileStatus::SUSPENDED->value,
        ]);

        $this->actingAs($courier, 'api')
            ->postJson('/api/v1/courier/shifts/start')
            ->assertForbidden();
    }
}
