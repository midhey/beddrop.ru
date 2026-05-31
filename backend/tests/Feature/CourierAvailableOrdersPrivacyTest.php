<?php

namespace Tests\Feature;

use App\Enums\CourierShiftStatus;
use App\Enums\OrderStatus;
use App\Models\OrderRouteSegment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesApiData;
use Tests\TestCase;

class CourierAvailableOrdersPrivacyTest extends TestCase
{
    use CreatesApiData;
    use RefreshDatabase;

    public function test_available_orders_hide_customer_pii_before_assignment(): void
    {
        $courier = $this->createUser();
        $customer = $this->createUser();
        $restaurantOwner = $this->createUser();
        $restaurantAddress = $this->createAddress(null, [
            'line1' => 'Набережная ресторана, 1',
            'lat' => 58.520000,
            'lng' => 31.270000,
        ]);
        $restaurant = $this->createRestaurant($restaurantOwner, ['address' => $restaurantAddress]);
        $deliveryAddress = $this->createAddress($customer, [
            'value' => 'Великий Новгород, Тайная улица, 10',
            'unrestricted_value' => '173000, Великий Новгород, Тайная улица, 10, кв 42',
            'line1' => 'Тайная улица, 10',
            'line2' => 'Скрытый корпус',
            'city' => 'Великий Новгород',
            'area' => 'Центральный район',
            'flat' => '42',
            'entrance' => '3',
            'floor' => '7',
            'intercom' => '4242',
            'lat' => 58.530001,
            'lng' => 31.290001,
            'raw_dadata_json' => ['secret' => 'customer raw dadata'],
        ]);
        $order = $this->createAcceptedOrder($customer, $restaurant, null, [
            'status' => OrderStatus::READY_FOR_PICKUP->value,
            'delivery_address_id' => $deliveryAddress->id,
            'delivery_lat' => 58.530001,
            'delivery_lng' => 31.290001,
            'delivery_distance_meters' => 2300,
            'delivery_duration_seconds' => 900,
        ]);

        OrderRouteSegment::create([
            'order_id' => $order->id,
            'segment_type' => 'restaurant_to_client',
            'mode' => 'auto',
            'distance_meters' => 2300,
            'duration_seconds' => 900,
            'encoded_shape' => 'encoded-customer-route',
            'raw_response_json' => ['shape' => 'raw-customer-route'],
        ]);

        $this->createCourierProfile($courier);

        $this
            ->actingAs($courier, 'api')
            ->postJson('/api/v1/courier/shifts/start')
            ->assertCreated()
            ->assertJsonPath('shift.status', CourierShiftStatus::OPEN->value);

        $response = $this
            ->actingAs($courier, 'api')
            ->getJson('/api/v1/courier/orders/available')
            ->assertOk()
            ->assertJsonPath('data.0.id', $order->id)
            ->assertJsonPath('data.0.delivery_address.city', 'Великий Новгород')
            ->assertJsonPath('data.0.delivery_address.area', 'Центральный район');

        $availableOrder = $response->json('data.0');
        $availableAddress = $availableOrder['delivery_address'];

        foreach ([
            'id',
            'unrestricted_value',
            'line1',
            'line2',
            'flat',
            'entrance',
            'floor',
            'intercom',
            'lat',
            'lng',
            'raw_dadata',
        ] as $field) {
            $this->assertArrayNotHasKey($field, $availableAddress);
        }

        foreach (['delivery_address_id', 'delivery_lat', 'delivery_lng', 'route_segments'] as $field) {
            $this->assertArrayNotHasKey($field, $availableOrder);
        }
    }

    public function test_assigned_courier_can_see_full_delivery_details(): void
    {
        $courier = $this->createUser();
        $customer = $this->createUser();
        $restaurantOwner = $this->createUser();
        $restaurant = $this->createRestaurant($restaurantOwner);
        $deliveryAddress = $this->createAddress($customer, [
            'value' => 'Великий Новгород, Тайная улица, 10',
            'line1' => 'Тайная улица, 10',
            'city' => 'Великий Новгород',
            'flat' => '42',
            'entrance' => '3',
            'floor' => '7',
            'intercom' => '4242',
            'lat' => 58.530001,
            'lng' => 31.290001,
            'raw_dadata_json' => ['secret' => 'customer raw dadata'],
        ]);
        $order = $this->createAcceptedOrder($customer, $restaurant, null, [
            'status' => OrderStatus::READY_FOR_PICKUP->value,
            'delivery_address_id' => $deliveryAddress->id,
            'delivery_lat' => 58.530001,
            'delivery_lng' => 31.290001,
        ]);

        OrderRouteSegment::create([
            'order_id' => $order->id,
            'segment_type' => 'restaurant_to_client',
            'mode' => 'auto',
            'distance_meters' => 2300,
            'duration_seconds' => 900,
            'encoded_shape' => 'encoded-customer-route',
            'raw_response_json' => ['shape' => 'raw-customer-route'],
        ]);

        $this->createCourierProfile($courier);

        $this
            ->actingAs($courier, 'api')
            ->postJson('/api/v1/courier/shifts/start')
            ->assertCreated();

        $this
            ->actingAs($courier, 'api')
            ->postJson("/api/v1/courier/orders/{$order->id}/assign")
            ->assertOk();

        $this
            ->actingAs($courier, 'api')
            ->getJson('/api/v1/courier/orders/active')
            ->assertOk()
            ->assertJsonPath('data.0.delivery_address.line1', 'Тайная улица, 10')
            ->assertJsonPath('data.0.delivery_address.flat', '42')
            ->assertJsonPath('data.0.delivery_address.entrance', '3')
            ->assertJsonPath('data.0.delivery_address.floor', '7')
            ->assertJsonPath('data.0.delivery_address.intercom', '4242')
            ->assertJsonPath('data.0.delivery_address.lat', 58.530001)
            ->assertJsonPath('data.0.delivery_address.lng', 31.290001)
            ->assertJsonPath('data.0.delivery_address.raw_dadata.secret', 'customer raw dadata')
            ->assertJsonPath('data.0.route_segments.0.encoded_shape', 'encoded-customer-route')
            ->assertJsonPath('data.0.route_segments.0.raw_response.shape', 'raw-customer-route');
    }
}
