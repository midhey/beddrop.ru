<?php

namespace Tests\Feature;

use App\Enums\CourierProfileStatus;
use App\Enums\CourierShiftStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\CourierLocation;
use App\Models\CourierShift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesApiData;
use Tests\TestCase;

class AdminApiTest extends TestCase
{
    use CreatesApiData;
    use RefreshDatabase;

    public function test_non_admin_cannot_access_admin_api(): void
    {
        $user = $this->createUser();

        $this
            ->actingAs($user, 'api')
            ->getJson('/api/v1/admin/dashboard')
            ->assertForbidden();
    }

    public function test_admin_can_list_core_resources(): void
    {
        $admin = $this->createUser(['is_admin' => true]);
        $customer = $this->createUser();
        $restaurant = $this->createRestaurant();
        $courier = $this->createUser();
        $this->createCourierProfile($courier);
        $this->createAcceptedOrder($customer, $restaurant);

        $this->actingAs($admin, 'api')->getJson('/api/v1/admin/users?search='.$courier->email)->assertOk()->assertJsonPath('data.0.id', $courier->id);
        $this->actingAs($admin, 'api')->getJson('/api/v1/admin/restaurants?search='.$restaurant->slug)->assertOk()->assertJsonPath('data.0.id', $restaurant->id);
        $this->actingAs($admin, 'api')->getJson('/api/v1/admin/couriers?search='.$courier->email)->assertOk()->assertJsonPath('data.0.user_id', $courier->id);
        $this->actingAs($admin, 'api')->getJson('/api/v1/admin/orders')->assertOk()->assertJsonPath('data.0.restaurant.id', $restaurant->id);
    }

    public function test_admin_user_update_writes_audit_log(): void
    {
        $admin = $this->createUser(['is_admin' => true]);
        $user = $this->createUser();

        $this
            ->actingAs($admin, 'api')
            ->patchJson("/api/v1/admin/users/{$user->id}", [
                'is_banned' => true,
            ])
            ->assertOk()
            ->assertJsonPath('user.is_banned', true);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_banned' => true,
        ]);
        $this->assertDatabaseHas('admin_action_logs', [
            'admin_user_id' => $admin->id,
            'action' => 'admin.user.update',
            'target_type' => 'App\Models\User',
            'target_id' => (string) $user->id,
        ]);
    }

    public function test_register_and_profile_update_cannot_mass_assign_admin_flags(): void
    {
        $this
            ->postJson('/api/v1/auth/register', [
                'email' => 'mass-assign@example.com',
                'phone' => '79990009999',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'name' => 'Mass Assign',
                'is_admin' => true,
                'is_banned' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('user.email', 'mass-assign@example.com');

        $this->assertDatabaseHas('users', [
            'email' => 'mass-assign@example.com',
            'is_admin' => false,
            'is_banned' => false,
        ]);

        $user = $this->createUser();

        $this
            ->actingAs($user, 'api')
            ->putJson('/api/v1/profile/me', [
                'name' => 'Updated Name',
                'is_admin' => true,
                'is_banned' => true,
            ])
            ->assertOk()
            ->assertJsonPath('user.name', 'Updated Name')
            ->assertJsonPath('user.is_admin', false)
            ->assertJsonPath('user.is_banned', false);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'is_admin' => false,
            'is_banned' => false,
        ]);
    }

    public function test_admin_update_can_change_admin_and_banned_flags(): void
    {
        $admin = $this->createUser(['is_admin' => true]);
        $user = $this->createUser();

        $this
            ->actingAs($admin, 'api')
            ->patchJson("/api/v1/admin/users/{$user->id}", [
                'is_admin' => true,
                'is_banned' => true,
            ])
            ->assertOk()
            ->assertJsonPath('user.is_admin', true)
            ->assertJsonPath('user.is_banned', true);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_admin' => true,
            'is_banned' => true,
        ]);
    }

    public function test_admin_cannot_suspend_courier_with_open_shift(): void
    {
        $admin = $this->createUser(['is_admin' => true]);
        $courierUser = $this->createUser();
        $courier = $this->createCourierProfile($courierUser);

        CourierShift::create([
            'courier_user_id' => $courierUser->id,
            'status' => CourierShiftStatus::OPEN->value,
        ]);

        $this
            ->actingAs($admin, 'api')
            ->patchJson("/api/v1/admin/couriers/{$courier->user_id}", [
                'status' => CourierProfileStatus::SUSPENDED->value,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status'])
            ->assertJsonPath('errors.status.0', 'Нельзя приостановить курьера с открытой сменой.');

        $this->assertDatabaseHas('courier_profiles', [
            'user_id' => $courierUser->id,
            'status' => CourierProfileStatus::ACTIVE->value,
        ]);
    }

    public function test_admin_cannot_suspend_courier_with_active_order(): void
    {
        $admin = $this->createUser(['is_admin' => true]);
        $courierUser = $this->createUser();
        $courier = $this->createCourierProfile($courierUser);
        $customer = $this->createUser();
        $restaurant = $this->createRestaurant();

        $this->createAcceptedOrder($customer, $restaurant, null, [
            'courier_id' => $courierUser->id,
            'status' => OrderStatus::PICKED_UP->value,
        ]);

        $this
            ->actingAs($admin, 'api')
            ->patchJson("/api/v1/admin/couriers/{$courier->user_id}", [
                'status' => CourierProfileStatus::SUSPENDED->value,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status'])
            ->assertJsonPath('errors.status.0', 'Нельзя приостановить курьера с активным заказом.');

        $this->assertDatabaseHas('courier_profiles', [
            'user_id' => $courierUser->id,
            'status' => CourierProfileStatus::ACTIVE->value,
        ]);
    }

    public function test_admin_can_suspend_courier_with_no_open_shift_and_no_active_orders(): void
    {
        $admin = $this->createUser(['is_admin' => true]);
        $courierUser = $this->createUser();
        $courier = $this->createCourierProfile($courierUser);

        $this
            ->actingAs($admin, 'api')
            ->patchJson("/api/v1/admin/couriers/{$courier->user_id}", [
                'status' => CourierProfileStatus::SUSPENDED->value,
            ])
            ->assertOk()
            ->assertJsonPath('courier.status', CourierProfileStatus::SUSPENDED->value);

        $this->assertDatabaseHas('courier_profiles', [
            'user_id' => $courierUser->id,
            'status' => CourierProfileStatus::SUSPENDED->value,
        ]);
    }

    public function test_admin_can_assign_courier_and_store_order_event(): void
    {
        config([
            'services.valhalla.url' => 'https://valhalla.test',
        ]);

        Http::fake([
            'valhalla.test/route' => Http::response([
                'trip' => [
                    'summary' => [
                        'length' => 1.2,
                        'time' => 360,
                    ],
                    'legs' => [
                        ['shape' => 'approach-shape'],
                    ],
                ],
            ]),
        ]);

        $admin = $this->createUser(['is_admin' => true]);
        $customer = $this->createUser();
        $restaurantAddress = $this->createAddress(null, ['lat' => 58.52, 'lng' => 31.27]);
        $restaurant = $this->createRestaurant(null, ['address' => $restaurantAddress]);
        $courier = $this->createUser();
        $this->createCourierProfile($courier);

        CourierShift::create([
            'courier_user_id' => $courier->id,
            'status' => CourierShiftStatus::OPEN->value,
        ]);
        CourierLocation::create([
            'courier_user_id' => $courier->id,
            'lat' => 58.51,
            'lng' => 31.26,
            'recorded_at' => now(),
        ]);

        $order = $this->createAcceptedOrder($customer, $restaurant, null, [
            'status' => OrderStatus::READY_FOR_PICKUP->value,
        ]);

        $this
            ->actingAs($admin, 'api')
            ->postJson("/api/v1/admin/orders/{$order->id}/assign-courier", [
                'courier_user_id' => $courier->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', OrderStatus::COURIER_ASSIGNED->value)
            ->assertJsonPath('data.courier_id', $courier->id);

        $this->assertDatabaseHas('order_events', [
            'order_id' => $order->id,
            'event' => OrderStatus::COURIER_ASSIGNED->value,
        ]);
        $this->assertDatabaseHas('order_route_segments', [
            'order_id' => $order->id,
            'segment_type' => 'courier_to_restaurant',
            'encoded_shape' => 'approach-shape',
        ]);
    }

    public function test_admin_order_transitions_validate_statuses(): void
    {
        $admin = $this->createUser(['is_admin' => true]);
        $customer = $this->createUser();
        $restaurant = $this->createRestaurant();
        $order = $this->createAcceptedOrder($customer, $restaurant);

        $this
            ->actingAs($admin, 'api')
            ->postJson("/api/v1/admin/orders/{$order->id}/delivered")
            ->assertStatus(422);

        $this
            ->actingAs($admin, 'api')
            ->postJson("/api/v1/admin/orders/{$order->id}/cancel", [
                'reason' => 'test',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', OrderStatus::CANCELED_BY_RESTAURANT->value);

        $this->assertDatabaseHas('order_events', [
            'order_id' => $order->id,
            'event' => OrderStatus::CANCELED_BY_RESTAURANT->value,
        ]);
    }

    public function test_admin_can_progress_order_through_transition_actions(): void
    {
        $admin = $this->createUser(['is_admin' => true]);
        $customer = $this->createUser();
        $restaurant = $this->createRestaurant();
        $courier = $this->createUser();
        $this->createCourierProfile($courier);

        CourierShift::create([
            'courier_user_id' => $courier->id,
            'status' => CourierShiftStatus::OPEN->value,
        ]);

        $order = $this->createAcceptedOrder($customer, $restaurant, null, [
            'status' => OrderStatus::CREATED->value,
            'payment_status' => PaymentStatus::PAID->value,
        ]);

        $this
            ->actingAs($admin, 'api')
            ->postJson("/api/v1/admin/orders/{$order->id}/accept")
            ->assertOk()
            ->assertJsonPath('data.status', OrderStatus::ACCEPTED_BY_RESTAURANT->value);

        $this
            ->actingAs($admin, 'api')
            ->postJson("/api/v1/admin/orders/{$order->id}/ready")
            ->assertOk()
            ->assertJsonPath('data.status', OrderStatus::READY_FOR_PICKUP->value);

        $this
            ->actingAs($admin, 'api')
            ->postJson("/api/v1/admin/orders/{$order->id}/assign-courier", [
                'courier_user_id' => $courier->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', OrderStatus::COURIER_ASSIGNED->value)
            ->assertJsonPath('data.courier_id', $courier->id);

        $this
            ->actingAs($admin, 'api')
            ->postJson("/api/v1/admin/orders/{$order->id}/picked-up")
            ->assertOk()
            ->assertJsonPath('data.status', OrderStatus::PICKED_UP->value);

        $this
            ->actingAs($admin, 'api')
            ->postJson("/api/v1/admin/orders/{$order->id}/delivered")
            ->assertOk()
            ->assertJsonPath('data.status', OrderStatus::DELIVERED->value);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::DELIVERED->value,
            'courier_id' => $courier->id,
        ]);

        foreach ([
            OrderStatus::ACCEPTED_BY_RESTAURANT,
            OrderStatus::READY_FOR_PICKUP,
            OrderStatus::COURIER_ASSIGNED,
            OrderStatus::PICKED_UP,
            OrderStatus::DELIVERED,
        ] as $status) {
            $this->assertDatabaseHas('order_events', [
                'order_id' => $order->id,
                'event' => $status->value,
            ]);
        }
    }

    public function test_admin_default_accept_rejects_unpaid_order(): void
    {
        $admin = $this->createUser(['is_admin' => true]);
        $customer = $this->createUser();
        $restaurant = $this->createRestaurant();
        $order = $this->createAcceptedOrder($customer, $restaurant, null, [
            'status' => OrderStatus::CREATED->value,
            'payment_status' => PaymentStatus::PENDING->value,
        ]);

        $this
            ->actingAs($admin, 'api')
            ->postJson("/api/v1/admin/orders/{$order->id}/accept")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Заказ еще не оплачен.');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::CREATED->value,
            'payment_status' => PaymentStatus::PENDING->value,
        ]);
    }

    public function test_admin_dashboard_returns_aggregates(): void
    {
        $admin = $this->createUser(['is_admin' => true]);
        $customer = $this->createUser();
        $restaurant = $this->createRestaurant();

        $this->createAcceptedOrder($customer, $restaurant, null, [
            'status' => OrderStatus::DELIVERED->value,
            'total_price' => 800,
            'delivery_price_snapshot' => 120,
            'delivery_duration_seconds' => 600,
        ]);
        $this->createAcceptedOrder($customer, $restaurant, null, [
            'status' => OrderStatus::CANCELED_BY_RESTAURANT->value,
            'total_price' => 500,
            'delivery_price_snapshot' => 100,
        ]);

        $this
            ->actingAs($admin, 'api')
            ->getJson('/api/v1/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('metrics.orders_total', 2)
            ->assertJsonPath('metrics.orders_delivered', 1)
            ->assertJsonPath('metrics.orders_cancelled', 1)
            ->assertJsonPath('metrics.gmv', 1300);
    }
}
