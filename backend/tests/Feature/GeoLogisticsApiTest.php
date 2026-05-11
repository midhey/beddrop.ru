<?php

namespace Tests\Feature;

use App\Enums\CourierShiftStatus;
use App\Enums\OrderStatus;
use App\Models\CourierShift;
use App\Models\OrderRouteSegment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesApiData;
use Tests\TestCase;

class GeoLogisticsApiTest extends TestCase
{
    use CreatesApiData;
    use RefreshDatabase;

    public function test_authenticated_user_can_fetch_dadata_suggestions(): void
    {
        config([
            'services.dadata.api_key' => 'test-token',
        ]);

        Http::fake([
            'suggestions.dadata.ru/*' => Http::response([
                'suggestions' => [
                    [
                        'value' => 'г Великий Новгород, ул Большая Санкт-Петербургская, д 1',
                        'unrestricted_value' => '173001, Новгородская обл, г Великий Новгород, ул Большая Санкт-Петербургская, д 1',
                        'data' => [
                            'geo_lat' => '58.5250',
                            'geo_lon' => '31.2750',
                            'city' => 'Великий Новгород',
                            'street_with_type' => 'ул Большая Санкт-Петербургская',
                            'house' => '1',
                            'qc_geo' => '0',
                        ],
                    ],
                ],
            ]),
        ]);

        $user = $this->createUser();

        $response = $this
            ->actingAs($user, 'api')
            ->getJson('/api/v1/geo/address-suggestions?q=' . urlencode('Великий Новгород Большая Санкт-Петербургская 1'));

        $response
            ->assertOk()
            ->assertJsonPath('suggestions.0.value', 'г Великий Новгород, ул Большая Санкт-Петербургская, д 1')
            ->assertJsonPath('suggestions.0.data.lat', 58.525)
            ->assertJsonPath('suggestions.0.data.lng', 31.275);
    }

    public function test_delivery_quote_uses_valhalla_and_database_settings(): void
    {
        config([
            'services.valhalla.url' => 'https://valhalla.test',
        ]);

        Http::fake([
            'valhalla.test/route' => Http::response([
                'trip' => [
                    'summary' => [
                        'length' => 4.2,
                        'time' => 780,
                    ],
                    'legs' => [
                        ['shape' => 'encoded-shape'],
                    ],
                ],
            ]),
        ]);

        $user = $this->createUser();
        $restaurantAddress = $this->createAddress(null, [
            'lat' => 58.52,
            'lng' => 31.27,
        ]);
        $restaurant = $this->createRestaurant(null, ['address' => $restaurantAddress]);
        $deliveryAddress = $this->createAddress($user, [
            'lat' => 58.53,
            'lng' => 31.28,
        ]);

        $response = $this
            ->actingAs($user, 'api')
            ->postJson('/api/v1/delivery/quote', [
                'restaurant_id' => $restaurant->id,
                'delivery_address_id' => $deliveryAddress->id,
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('quote.distance_meters', 4200)
            ->assertJsonPath('quote.duration_seconds', 780)
            ->assertJsonPath('quote.eta_minutes', 44)
            ->assertJsonPath('quote.delivery_price', 314)
            ->assertJsonPath('quote.route.encoded_shape', 'encoded-shape');
    }

    public function test_courier_can_store_location_only_with_open_shift(): void
    {
        $courier = $this->createUser();
        $this->createCourierProfile($courier);

        $withoutShift = $this
            ->actingAs($courier, 'api')
            ->postJson('/api/v1/courier/location', [
                'lat' => 58.52,
                'lng' => 31.27,
            ]);

        $withoutShift->assertStatus(422);

        CourierShift::create([
            'courier_user_id' => $courier->id,
            'status' => CourierShiftStatus::OPEN->value,
        ]);

        $response = $this
            ->actingAs($courier, 'api')
            ->postJson('/api/v1/courier/location', [
                'lat' => 58.52,
                'lng' => 31.27,
                'accuracy' => 12.5,
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('courier_locations', [
            'courier_user_id' => $courier->id,
        ]);
    }

    public function test_available_courier_orders_include_full_preview_route(): void
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

        $courier = $this->createUser();
        $customer = $this->createUser();
        $restaurantOwner = $this->createUser();
        $restaurantAddress = $this->createAddress(null, [
            'lat' => 58.52,
            'lng' => 31.27,
        ]);
        $restaurant = $this->createRestaurant($restaurantOwner, [
            'address' => $restaurantAddress,
        ]);
        $product = $this->createProduct($restaurant);

        $this->createCourierProfile($courier);

        CourierShift::create([
            'courier_user_id' => $courier->id,
            'status' => CourierShiftStatus::OPEN->value,
        ]);

        $this
            ->actingAs($courier, 'api')
            ->postJson('/api/v1/courier/location', [
                'lat' => 58.51,
                'lng' => 31.26,
            ])
            ->assertCreated();

        $order = $this->createAcceptedOrder($customer, $restaurant, $product, [
            'status' => OrderStatus::READY_FOR_PICKUP->value,
        ]);

        OrderRouteSegment::create([
            'order_id' => $order->id,
            'segment_type' => 'restaurant_to_client',
            'mode' => 'auto',
            'distance_meters' => 4200,
            'duration_seconds' => 780,
            'encoded_shape' => 'delivery-shape',
        ]);

        $this
            ->actingAs($courier, 'api')
            ->getJson('/api/v1/courier/orders/available')
            ->assertOk()
            ->assertJsonPath('data.0.route_segments.0.segment_type', 'courier_to_restaurant')
            ->assertJsonPath('data.0.route_segments.0.encoded_shape', 'approach-shape')
            ->assertJsonPath('data.0.route_segments.1.segment_type', 'restaurant_to_client')
            ->assertJsonPath('data.0.route_segments.1.encoded_shape', 'delivery-shape');

        $this->assertDatabaseMissing('order_route_segments', [
            'order_id' => $order->id,
            'segment_type' => 'courier_to_restaurant',
        ]);
    }

    public function test_admin_can_manage_logistics_settings(): void
    {
        $admin = $this->createUser(['is_admin' => true]);
        $user = $this->createUser();

        $this
            ->actingAs($user, 'api')
            ->getJson('/api/v1/admin/logistics/settings')
            ->assertForbidden();

        $this
            ->actingAs($admin, 'api')
            ->getJson('/api/v1/admin/logistics/settings')
            ->assertOk()
            ->assertJsonStructure(['groups' => ['pricing']]);

        $this
            ->actingAs($admin, 'api')
            ->putJson('/api/v1/admin/logistics/settings', [
                'settings' => [
                    'delivery.base_price' => 199,
                ],
            ])
            ->assertOk();

        $this->assertDatabaseHas('logistics_settings', [
            'key' => 'delivery.base_price',
            'value' => '199',
        ]);
    }

    public function test_admin_can_debug_dadata_address(): void
    {
        config([
            'services.dadata.api_key' => 'test-token',
            'services.dadata.secret_key' => 'test-secret',
        ]);

        Http::fake([
            'cleaner.dadata.ru/*' => Http::response([
                [
                    'result' => 'г Великий Новгород, ул Большая Московская, д 10',
                    'geo_lat' => '58.5176735',
                    'geo_lon' => '31.2866066',
                    'city' => 'Великий Новгород',
                    'street_with_type' => 'ул Большая Московская',
                    'house' => '10',
                    'qc_geo' => '0',
                ],
            ]),
        ]);

        $admin = $this->createUser(['is_admin' => true]);

        $this
            ->actingAs($admin, 'api')
            ->postJson('/api/v1/admin/logistics/test-address', [
                'address' => 'Великий Новгород, Большая Московская, 10',
            ])
            ->assertOk()
            ->assertJsonPath('address.value', 'г Великий Новгород, ул Большая Московская, д 10')
            ->assertJsonPath('address.data.lat', 58.5176735)
            ->assertJsonPath('address.data.lng', 31.2866066)
            ->assertJsonPath('address.data.qc_geo', 0);
    }

    public function test_admin_can_debug_valhalla_routes(): void
    {
        config([
            'services.valhalla.url' => 'https://valhalla.test',
        ]);

        Http::fake([
            'valhalla.test/route' => Http::response([
                'trip' => [
                    'summary' => [
                        'length' => 1.5,
                        'time' => 300,
                    ],
                    'legs' => [
                        ['shape' => 'route-shape'],
                    ],
                ],
            ]),
        ]);

        $admin = $this->createUser(['is_admin' => true]);

        $routeResponse = $this
            ->actingAs($admin, 'api')
            ->postJson('/api/v1/admin/logistics/test-route', [
                'from' => ['lat' => 58.52, 'lng' => 31.27],
                'to' => ['lat' => 58.53, 'lng' => 31.28],
                'mode' => 'auto',
            ]);

        $routeResponse
            ->assertOk()
            ->assertJsonPath('route.distance_meters', 1500)
            ->assertJsonPath('route.encoded_shape', 'route-shape');
    }

    public function test_admin_can_view_order_route_segments(): void
    {
        $admin = $this->createUser(['is_admin' => true]);
        $customer = $this->createUser();
        $restaurant = $this->createRestaurant();
        $order = $this->createAcceptedOrder($customer, $restaurant);

        OrderRouteSegment::create([
            'order_id' => $order->id,
            'segment_type' => 'restaurant_to_client',
            'mode' => 'auto',
            'distance_meters' => 4200,
            'duration_seconds' => 780,
            'encoded_shape' => 'encoded-shape',
        ]);

        $this
            ->actingAs($admin, 'api')
            ->getJson("/api/v1/admin/orders/{$order->id}/routes")
            ->assertOk()
            ->assertJsonPath('order_id', $order->id)
            ->assertJsonPath('route_segments.0.segment_type', 'restaurant_to_client')
            ->assertJsonPath('route_segments.0.encoded_shape', 'encoded-shape');
    }
}
