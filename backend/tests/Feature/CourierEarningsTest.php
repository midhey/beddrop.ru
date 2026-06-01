<?php

namespace Tests\Feature;

use App\Enums\CourierProfileStatus;
use App\Enums\OrderStatus;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesApiData;
use Tests\TestCase;

class CourierEarningsTest extends TestCase
{
    use CreatesApiData;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_active_courier_gets_zero_earnings_without_deliveries(): void
    {
        $courier = $this->createUser();
        $this->createCourierProfile($courier);

        $this->actingAs($courier, 'api')
            ->getJson('/api/v1/courier/earnings')
            ->assertOk()
            ->assertJsonPath('today.deliveries_count', 0)
            ->assertJsonPath('today.earnings_sum', '0.00')
            ->assertJsonPath('today.total_orders_sum', '0.00')
            ->assertJsonPath('week.deliveries_count', 0)
            ->assertJsonPath('total.deliveries_count', 0);
    }

    public function test_courier_earnings_include_only_own_delivered_orders_by_period(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-04 12:00:00'));

        $courier = $this->createUser();
        $otherCourier = $this->createUser();
        $customer = $this->createUser();
        $restaurantOwner = $this->createUser();
        $restaurant = $this->createRestaurant($restaurantOwner);
        $product = $this->createProduct($restaurant);

        $this->createCourierProfile($courier);
        $this->createCourierProfile($otherCourier);

        $this->createDeliveredOrder($courier->id, $customer, $restaurant, $product, [
            'total_price' => 1000,
            'courier_fee' => 200,
            'updated_at' => now()->subHour(),
        ]);
        $this->createDeliveredOrder($courier->id, $customer, $restaurant, $product, [
            'total_price' => 800,
            'courier_fee' => 160,
            'updated_at' => now()->subDays(2),
        ]);
        $this->createDeliveredOrder($courier->id, $customer, $restaurant, $product, [
            'total_price' => 500,
            'courier_fee' => 100,
            'updated_at' => now()->subWeeks(2),
        ]);
        $this->createDeliveredOrder($otherCourier->id, $customer, $restaurant, $product, [
            'total_price' => 999,
            'courier_fee' => 999,
            'updated_at' => now()->subHour(),
        ]);
        $this->createAcceptedOrder($customer, $restaurant, $product, [
            'courier_id' => $courier->id,
            'status' => OrderStatus::PICKED_UP->value,
            'total_price' => 700,
            'courier_fee' => 140,
            'updated_at' => now()->subHour(),
        ]);
        $this->createAcceptedOrder($customer, $restaurant, $product, [
            'courier_id' => $courier->id,
            'status' => OrderStatus::CANCELED_BY_USER->value,
            'total_price' => 600,
            'courier_fee' => 120,
            'updated_at' => now()->subHour(),
        ]);

        $this->actingAs($courier, 'api')
            ->getJson('/api/v1/courier/earnings')
            ->assertOk()
            ->assertJsonPath('today.deliveries_count', 1)
            ->assertJsonPath('today.earnings_sum', '200.00')
            ->assertJsonPath('today.total_orders_sum', '1000.00')
            ->assertJsonPath('week.deliveries_count', 2)
            ->assertJsonPath('week.earnings_sum', '360.00')
            ->assertJsonPath('week.total_orders_sum', '1800.00')
            ->assertJsonPath('total.deliveries_count', 3)
            ->assertJsonPath('total.earnings_sum', '460.00')
            ->assertJsonPath('total.total_orders_sum', '2300.00');
    }

    public function test_courier_earnings_fallback_to_payout_calculator_when_stored_fee_is_empty(): void
    {
        $courier = $this->createUser();
        $customer = $this->createUser();
        $restaurantOwner = $this->createUser();
        $restaurant = $this->createRestaurant($restaurantOwner);
        $product = $this->createProduct($restaurant);

        $this->createCourierProfile($courier);
        $this->createDeliveredOrder($courier->id, $customer, $restaurant, $product, [
            'total_price' => 900,
            'courier_fee' => 0,
            'delivery_price_snapshot' => 250,
            'updated_at' => now()->subHour(),
        ]);

        $this->actingAs($courier, 'api')
            ->getJson('/api/v1/courier/earnings')
            ->assertOk()
            ->assertJsonPath('today.deliveries_count', 1)
            ->assertJsonPath('today.earnings_sum', '200.00')
            ->assertJsonPath('today.total_orders_sum', '900.00');
    }

    public function test_courier_earnings_require_active_courier_profile(): void
    {
        $plainUser = $this->createUser();
        $suspendedCourier = $this->createUser();

        $this->createCourierProfile($suspendedCourier, [
            'status' => CourierProfileStatus::SUSPENDED->value,
        ]);

        $this->actingAs($plainUser, 'api')
            ->getJson('/api/v1/courier/earnings')
            ->assertForbidden();

        $this->actingAs($suspendedCourier, 'api')
            ->getJson('/api/v1/courier/earnings')
            ->assertForbidden();
    }

    private function createDeliveredOrder(
        int $courierId,
        $customer,
        $restaurant,
        $product,
        array $attributes = [],
    ) {
        $updatedAt = $attributes['updated_at'] ?? null;
        unset($attributes['updated_at']);

        $order = $this->createAcceptedOrder($customer, $restaurant, $product, array_merge([
            'courier_id' => $courierId,
            'status' => OrderStatus::DELIVERED->value,
        ], $attributes));

        if ($updatedAt !== null) {
            $order->forceFill(['updated_at' => $updatedAt])->save();
        }

        return $order->fresh();
    }
}
